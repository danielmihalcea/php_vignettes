<?
/********************************************************************************/
/* générateur automatique de vignettes en ligne                                 */
/* gère les formats JPEG, PNG et GIF, préserve le cannal alpha des PNG          */
/*                                                                              */
/* copyright Daniel MIHALCEA (c) 2011 http://mihalcea.fr/                       */
/*                                                                              */
/* @param   {File}   f: fichier pointant vers l'image source ou URL             */
/* @param   {Number} [h=128]: taille de la vignette, 128 pixels par défaut      */
/* @param   {Bool}   [r=0]: si les vignettes doivent êtres carrées ou non       */
/* @returns {Image}  vignette de l'image                                        */
/*                                                                              */
/********************************************************************************/

$f = ( (isset($_GET['f'])) ? $_GET['f'] : '' ); // on récupère le nom du fichier
$h = (int) ( (isset($_GET['h'])) ? $_GET['h'] : '128' ); // on récupère la hauteur
$r = (bool) ( (isset($_GET['r'])) ? $_GET['r'] : '0' ); // doit-on générer une vignette carrée ?

if ($f{0} == '/') $f = $_SERVER['DOCUMENT_ROOT'].$f; // si le chemin est lié à la racine, on prends la racine du serveur
if (!is_numeric ($h) || $h<1) $h = 128; // si la dimention est incorrecte on prends la dimention par défaut

function error($txt) {
    global $h, $im1;
    $im1 = ImageCreateTrueColor($h, $h);
    $blanc = imagecolorallocate($im1, 255, 255, 255);
    imagestring($im1, 2, 2, 0, $txt, $blanc);
    finalise();
    exit;
}

function finalise() {
    global $ext, $im1;
    switch ($ext) { // on génère l'image finale selon le format
        case 'jpg' :
        case 'jpeg' : 
            imagejpeg($im1);
            break;
        case 'gif' :
            imagegif($im1);
            break;
        case 'png' :
        default :
            imagepng($im1);
            break;
    }
    imagedestroy($im1);
}


if (!is_file($f) && strtolower(substr($f, 0, 7)!='http://')) { // si le fichier n'existe pas, on affiche un message et on quitte
    header('Content-Type: image/png');
    error('fichier introuvable');
}

$ext = strtolower(substr($f, strrpos($f, '.') + 1)); // on récupère l'extenssion du fichier
switch ($ext) { // on envoie l'entête correspondant et on récupère l'image
    case 'jpg' :
    case 'jpeg' : 
        header('Content-Type: image/jpeg');
        $im0 = @imagecreatefromjpeg($f);
        break;
    case 'gif' :
        header('Content-Type: image/gif');
        $im0 = @imagecreatefromgif($f);
        break;
    case 'png' :
        header('Content-Type: image/png');
        $im0 = @imagecreatefrompng($f);
        break;
    default : // le type de l'image n'est pas supporté
        header('Content-Type: image/png');
        $im0 = ImageCreateTrueColor($h, $h);
        $blanc = imagecolorallocate($im0, 255, 255, 255);
        $r = 'fichier non supporté';
        imagestring($im0, 2, 2, 0, $r, $blanc);
        imagepng($im0);
        exit;
}

if (!$im0) {
    error('erreur d\'access');
}
// on récupère les dimensions de l'image originale
$h0 = ImageSY($im0);
$l0 = ImageSX($im0);

if ($r == '1') { // si les vignettes sont carrées
    $im1 = ImageCreateTrueColor($h, $h);
    ImageAlphaBlending($im1, false);
    imagesavealpha($im1, true);
    if ($h0 < $l0) { // si l'image est en mode paysage
        $h1 = $h;
        $l1 = floor($l0/$h0*$h1);
        imagecopyresampled ($im1, $im0, ($h1-$l1)/2, 0, 0, 0, $l1, $h1, $l0, $h0);
    } else { // ou portrait
        $l1 = $h;
        $h1 = floor($h0/$l0*$l1);
        imagecopyresampled ($im1, $im0, 0, ($l1-$h1)/2, 0, 0, $l1, $h1, $l0, $h0);
    }
} else { // ou sinon on respecte les proportions d'origine
    if ($h0 > $l0) { // si l'image est en mode portrait
        $h1 = $h;
        $l1 = floor($l0/$h0*$h1);
    } else { // ou paysage
        $l1 = $h;
        $h1 = floor($h0/$l0*$l1);
    }
    $im1 = ImageCreateTrueColor($l1, $h1);
    ImageAlphaBlending($im1, false);
    imagesavealpha($im1, true);
    imagecopyresampled ($im1, $im0, 0, 0, 0, 0, $l1, $h1, $l0, $h0);
}
imagedestroy($im0);
finalise();
?>
