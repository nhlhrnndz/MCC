<?php
require_once 'vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

echo "DOMPDF IS NOW WORKING PERFECTLY! 🚀<br><br>";

$options = new Options();
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

$html = '
<h1 style="color: green; text-align: center;">Malaruhatan Country Club</h1>
<p style="text-align: center; font-size: 20px;">Your PDF generator is ready!</p>
<p>If you see the PDF below → everything is 100% good to go!</p>
';

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("Malaruhatan-Test.pdf", ["Attachment" => false]);
?>