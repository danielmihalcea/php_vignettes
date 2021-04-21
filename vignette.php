<?php
/********************************************************************************/
/* générateur automatique de vignettes en ligne                                 */
/* gère les formats JPEG, PNG et GIF, préserve le cannal alpha des PNG          */
/*                                                                              */
/* copyright Daniel MIHALCEA (c) 2011-2021 http://mihalcea.fr/                  */
/*                                                                              */
/* @param   {File}   f: fichier pointant vers l'image source ou URL, peut être  */
/*                   une URL si allow_url_fopen est activé dans php.ini         */
/* @param   {Number} [h=128]: taille de la vignette, 128 pixels par défaut      */
/* @param   {Bool}   [r=0]: si les vignettes doivent êtres carrées ou non       */
/* @returns {Image}  vignette de l'image                                        */
/*                                                                              */
/********************************************************************************/

$f = $_GET['f'] ?? ''; // on récupère le nom du fichier s'il existe
$h = (int) ( $_GET['h'] ?? 128 ); // on récupère la hauteur, 128 pixels si non défini
$r = (bool) ( $_GET['r'] ?? false ); // doit-on générer une vignette carrée ? Par défaut non

if ($f{0} == '/') $f = $_SERVER['DOCUMENT_ROOT'].$f; // si le chemin est lié à la racine, on prends la racine du serveur HTTP
if (!is_numeric ($h) || $h<1) $h = 128; // si la dimension est incorrecte/invalide on prens la dimension par défaut
$ext = strtolower(substr($f, strrpos($f, '.') + 1)); // on récupère l'extension du fichier

function error(string $txt): void { // génère le message d'erreur en temps qu'image pour pouvoir le voir
    global $h, $im1;
    $im1 = ImageCreateTrueColor($h, $h);
    $blanc = imagecolorallocate($im1, 255, 255, 255);
    imagestring($im1, 2, 2, 0, utf8_decode($txt), $blanc);
    finalise();
    exit;
}

function finalise() { // finalise l'image : la génère selon le format initial
    global $ext, $im1;
    switch ($ext) {
        case 'jpg' :
        case 'jpeg' : 
            imagejpeg($im1);
            break;
        case 'gif' :
            imagegif($im1);
            break;
        case 'png' :
        default : // format PNG par défaut
            imagepng($im1);
            break;
    }
    imagedestroy($im1);
}

if (!is_file($f) && strtolower(substr($f, 0, 7)!='http://') && strtolower(substr($f, 0, 8)!='https://')) { // si le fichier n'existe pas sur le serveur et n'est pas une URL, on affiche un message et on quitte
    header('Content-Type: image/png');
    error('fichier introuvable');
}

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
        error('fichier non supporté');
}

if (!$im0) {
    error('erreur d\'access');
}
// on récupère les dimensions de l'image originale
$h0 = ImageSY($im0); // hauteur
$l0 = ImageSX($im0); // largeur

$x = 0; $y = 0;
if ($r == '1') { // si les vignettes sont carrées
    if ($h0 < $l0) { // si l'image est en mode paysage
        $h1 = $h;
        $l1 = floor($l0/$h0*$h1);
        $x = ($h1-$l1)/2;
    } else { // ou portrait
        $l1 = $h;
        $h1 = floor($h0/$l0*$l1);
        $y = ($l1-$h1)/2;
    }
    $im1 = ImageCreateTrueColor($h, $h);
} else { // ou sinon on respecte les proportions d'origine
    if ($h0 > $l0) { // si l'image est en mode portrait
        $h1 = $h;
        $l1 = floor($l0/$h0*$h1);
    } else { // ou paysage
        $l1 = $h;
        $h1 = floor($h0/$l0*$l1);
    }
    $im1 = ImageCreateTrueColor($l1, $h1);
}
ImageAlphaBlending($im1, false); // doit être à false pour imagesavealpha
imagesavealpha($im1, true); // permet de conserver le canal alpha
$r = imagecopyresampled ($im1, $im0, $x, $y, 0, 0, $l1, $h1, $l0, $h0); // redimensionne l'image 
imagedestroy($im0); // libère la mémoire de suite avant de générer l'image au format choisi
finalise();
