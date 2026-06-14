<?php
/**
 * Learn Way - Génération et téléchargement du certificat PDF (FPDF)
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole('etudiant');

require_once __DIR__ . '/../vendor/fpdf.php';

$moduleId  = intval($_GET['module_id'] ?? 0);
$studentId = intval($_SESSION['user_id']);

if ($moduleId <= 0) {
    http_response_code(400);
    die("Paramètre module_id manquant.");
}

// Vérifier l'inscription
$stmtE = $pdo->prepare('SELECT 1 FROM enrollments WHERE student_id = :sid AND module_id = :mid');
$stmtE->execute(['sid' => $studentId, 'mid' => $moduleId]);
if (!$stmtE->fetch()) {
    http_response_code(403);
    die("Vous n'êtes pas inscrit à ce module.");
}

// Récupérer le certificat
$stmtC = $pdo->prepare('
    SELECT c.*, u.first_name, u.last_name, m.title as module_title
    FROM certifications c
    JOIN users u ON c.student_id = u.id
    JOIN modules m ON c.module_id = m.id
    WHERE c.student_id = :sid AND c.module_id = :mid
');
$stmtC->execute(['sid' => $studentId, 'mid' => $moduleId]);
$cert = $stmtC->fetch();

if (!$cert) {
    // Vérifier si la progression est à 100 % pour générer le certificat à la volée
    $stmtL = $pdo->prepare('
        SELECT l.id as lesson_id, e.id as evaluation_id
        FROM lessons l JOIN courses c ON l.course_id = c.id
        LEFT JOIN evaluations e ON l.id = e.lesson_id
        WHERE c.module_id = :mid
    ');
    $stmtL->execute(['mid' => $moduleId]);
    $lessons = $stmtL->fetchAll();

    if (empty($lessons)) {
        http_response_code(404);
        die("Ce module ne contient aucune leçon.");
    }

    $totalPct = 0;
    foreach ($lessons as $l) {
        if (empty($l['evaluation_id'])) {
            $totalPct += 100;
        } else {
            $stmtA = $pdo->prepare('SELECT MAX(percentage) FROM evaluation_attempts WHERE student_id = :sid AND evaluation_id = :eid');
            $stmtA->execute(['sid' => $studentId, 'eid' => $l['evaluation_id']]);
            $totalPct += floatval($stmtA->fetchColumn());
        }
    }
    $progress = $totalPct / count($lessons);

    if ($progress < 100) {
        http_response_code(403);
        die("Module non validé à 100 %. Progression actuelle : " . round($progress, 1) . "%");
    }

    // Générer le certificat
    $certNum  = sprintf('LW-%s-%d-%d-%s', date('Ymd'), $studentId, $moduleId, strtoupper(substr(bin2hex(random_bytes(4)), 0, 8)));
    $sigHash  = hash('sha256', $studentId . '-' . $moduleId . '-' . $certNum);
    $stmtIns  = $pdo->prepare('INSERT INTO certifications (certificate_number, student_id, module_id, signature_hash) VALUES (:num, :sid, :mid, :hash)');
    $stmtIns->execute(['num' => $certNum, 'sid' => $studentId, 'mid' => $moduleId, 'hash' => $sigHash]);

    $stmtC->execute(['sid' => $studentId, 'mid' => $moduleId]);
    $cert = $stmtC->fetch();
}

// ============================================================
// GÉNÉRATION PDF avec FPDF
// ============================================================
class LearnWayCertificate extends FPDF {
    public function Header() { /* Entête gérée dans le corps */ }
    public function Footer() { /* Pied de page dans le corps */ }

    // Implémentation du dessin d'une ellipse avec FPDF
    public function Ellipse($x, $y, $rx, $ry, $style='D') {
        if($style=='F')
            $op='f';
        elseif($style=='FD' || $style=='DF')
            $op='B';
        else
            $op='S';
        $lx=4/3*(M_SQRT2-1)*$rx;
        $ly=4/3*(M_SQRT2-1)*$ry;
        $k=$this->k;
        $h=$this->h;
        $this->_out(sprintf('%.2F %.2F m',($x+$rx)*$k,($h-$y)*$k));
        $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c',($x+$rx)*$k,($h-($y-$ly))*$k,($x+$lx)*$k,($h-($y-$ry))*$k,$x*$k,($h-($y-$ry))*$k));
        $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c',($x-$lx)*$k,($h-($y-$ry))*$k,($x-$rx)*$k,($h-($y-$ly))*$k,($x-$rx)*$k,($h-$y)*$k));
        $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c',($x-$rx)*$k,($h-($y+$ly))*$k,($x-$lx)*$k,($h-($y+$ry))*$k,$x*$k,($h-($y+$ry))*$k));
        $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c',($x+$lx)*$k,($h-($y+$ry))*$k,($x+$rx)*$k,($h-($y-$ly))*$k,($x+$rx)*$k,($h-$y)*$k));
        $this->_out($op);
    }
}

$pdf = new LearnWayCertificate('L', 'mm', 'A4');
$pdf->AddPage();

// Dimensions A4 paysage
$w = 297;
$h = 210;

// ------ ARRIÈRE-PLAN ------
$pdf->SetFillColor(10, 25, 47);       // #0a192f
$pdf->Rect(0, 0, $w, $h, 'F');

// Bordure extérieure dorée
$pdf->SetDrawColor(100, 200, 218);     // teal
$pdf->SetLineWidth(1.5);
$pdf->Rect(8, 8, $w - 16, $h - 16);

// Bordure intérieure fine
$pdf->SetDrawColor(50, 100, 130);
$pdf->SetLineWidth(0.5);
$pdf->Rect(12, 12, $w - 24, $h - 24);

// ------ EN-TÊTE : Nom de la plateforme ------
$pdf->SetFont('Helvetica', 'B', 28);
$pdf->SetTextColor(100, 255, 218);     // accent
$pdf->SetXY(0, 20);
$pdf->Cell($w, 14, 'LEARN WAY', 0, 1, 'C');

$pdf->SetFont('Helvetica', '', 11);
$pdf->SetTextColor(136, 146, 176);
$pdf->Cell($w, 7, 'Plateforme d\'apprentissage en ligne', 0, 1, 'C');

// Ligne décorative
$pdf->SetDrawColor(100, 255, 218);
$pdf->SetLineWidth(0.8);
$pdf->Line(60, 47, $w - 60, 47);

// ------ TITRE DU CERTIFICAT ------
$pdf->SetFont('Helvetica', 'B', 18);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetXY(0, 53);
$pdf->Cell($w, 12, 'CERTIFICAT DE REUSSITE', 0, 1, 'C');

// ------ TEXTE PRINCIPAL ------
$pdf->SetFont('Helvetica', '', 12);
$pdf->SetTextColor(136, 146, 176);
$pdf->SetXY(0, 72);
$pdf->Cell($w, 8, 'Ce certificat est decerne a', 0, 1, 'C');

// ------ NOM DE L'ÉTUDIANT ------
$studentName = strtoupper($cert['first_name'] . ' ' . $cert['last_name']);
$pdf->SetFont('Helvetica', 'B', 26);
$pdf->SetTextColor(100, 255, 218);
$pdf->SetXY(0, 82);
$pdf->Cell($w, 14, $studentName, 0, 1, 'C');

// Soulignement du nom
$pdf->SetDrawColor(100, 255, 218);
$pdf->SetLineWidth(0.6);
$nameWidth = $pdf->GetStringWidth($studentName) * 1.05;
$pdf->Line(($w - $nameWidth) / 2, 98, ($w + $nameWidth) / 2, 98);

// ------ TEXTE INTERMÉDIAIRE ------
$pdf->SetFont('Helvetica', '', 12);
$pdf->SetTextColor(136, 146, 176);
$pdf->SetXY(0, 103);
$pdf->Cell($w, 8, 'pour avoir valide avec succes le module de formation :', 0, 1, 'C');

// ------ NOM DU MODULE ------
$pdf->SetFont('Helvetica', 'B', 16);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetXY(20, 113);
$pdf->MultiCell($w - 40, 9, utf8_decode($cert['module_title']), 0, 'C');

// ------ LIGNE DE SÉPARATION ------
$pdf->SetDrawColor(29, 53, 87);
$pdf->SetLineWidth(0.4);
$pdf->Line(40, 138, $w - 40, 138);

// ------ PIED DE PAGE : Date, N° de certificat, Signature ------
$pdf->SetFont('Helvetica', '', 9);
$pdf->SetTextColor(100, 110, 140);

// Zone gauche : date
$pdf->SetXY(30, 147);
$pdf->Cell(70, 6, 'Date de validation', 0, 1, 'C');
$pdf->SetFont('Helvetica', 'B', 10);
$pdf->SetTextColor(204, 214, 246);
$pdf->SetX(30);
$pdf->Cell(70, 6, date('d/m/Y', strtotime($cert['issue_date'])), 0, 1, 'C');

// Zone centrale : numéro de certificat
$pdf->SetFont('Helvetica', '', 9);
$pdf->SetTextColor(100, 110, 140);
$pdf->SetXY(($w / 2) - 50, 147);
$pdf->Cell(100, 6, 'Numero de certificat', 0, 1, 'C');
$pdf->SetFont('Helvetica', 'B', 9);
$pdf->SetTextColor(100, 255, 218);
$pdf->SetX(($w / 2) - 50);
$pdf->Cell(100, 6, $cert['certificate_number'], 0, 1, 'C');

// Zone droite : signature numérique (hash court)
$pdf->SetFont('Helvetica', '', 9);
$pdf->SetTextColor(100, 110, 140);
$pdf->SetXY($w - 110, 147);
$pdf->Cell(80, 6, 'Signature numerique', 0, 1, 'C');
$pdf->SetFont('Courier', '', 7);
$pdf->SetTextColor(126, 87, 194);
$pdf->SetX($w - 110);
$shortHash = substr($cert['signature_hash'], 0, 24) . '…';
$pdf->Cell(80, 6, $shortHash, 0, 1, 'C');

// Cachet / Sceau Learn Way
$pdf->SetFont('Helvetica', 'B', 10);
$pdf->SetTextColor(100, 255, 218);
$pdf->SetDrawColor(100, 255, 218);
$pdf->SetLineWidth(1);
$sealX = ($w / 2) - 18; $sealY = 158;
$pdf->Ellipse($sealX + 18, $sealY + 9, 20, 10);
$pdf->SetXY($sealX, $sealY + 4);
$pdf->Cell(36, 10, 'LEARN WAY', 0, 1, 'C');

// ------ Sortie ------
$filename = 'Certificat_LearnWay_' . str_replace(' ', '_', $cert['first_name'] . '_' . $cert['last_name']) . '.pdf';
$pdf->Output('D', $filename);
exit;
