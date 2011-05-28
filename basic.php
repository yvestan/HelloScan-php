<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4 ff=unix fenc=utf8: */

/**
*
* HelloScan Example
*
* @package HelloScan_PrestaShop
* @author Yves Tannier [grafactory.net]
* @copyright 2011 Yves Tannier
* @link http://helloscan.mobi
* @version 0.1
* @license MIT Licence
*/

/**
 * Exemple basique d'utilisation de HelloScan
 * http://helloscan.mobi
 */

 /* Table Mysql 'inscrits'
 CREATE TABLE `inscrits` (
 `id` INT( 10 ) UNSIGNED NOT NULL ,
 `nom` VARCHAR( 100 ) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL ,
 `prenom` VARCHAR( 100 ) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL ,
 `actif` TINYINT( 1 ) NOT NULL DEFAULT '0'
 ) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_bin;
 */

// configuration de l'accès à votre base de données
define('HELLO_HOST', 'sql_host');
define('HELLO_USER', 'sql_user');
define('HELLO_PASS', 'sql_pass');
define('HELLO_DB', 'sql_database');

// votre table d'inscrits
define('HELLO_TABLE', 'inscrits');

// votre champ d'identifiant présent dans le QRCode
define('HELLO_FIELD', 'id');

// le champ qui va marquer l'utilisateur actif 
define('HELLO_ACTIVE_FIELD', 'actif');

// exemple de clé d'authentification
define('HELLO_KEY', 'MaCleHelloScan');

// les erreur de requêtes
function sendError($fonction,$sql=null) {
    $response = array(
        'status' => 404,
        'result' => $fonction.' : Impossible d\'exécuter la requête',
    );
    // plus verbeux
    $response['result']  = $fonction.' : Impossible d\'exécuter la requête '.mysql_error().';'.$sql;
    echo json_encode($response);
    exit;
}

// connecter/tester la connexion à la base
$connect = mysql_connect(HELLO_HOST, HELLO_USER, HELLO_PASS);
if (!$connect) {
    sendError('connectDB');
    exit;
}
$select_database = mysql_select_db(HELLO_DB, $connect);
if (!$select_database) {
    sendError('selectDatabase');
    exit;
}

// tester si l'identifiant est présent dans la table
function checkId($id) {
    // on compte les résultats
    $sql_select = '
        SELECT COUNT(*) 
        FROM '.HELLO_TABLE.'
        WHERE '.HELLO_FIELD.'='.mysql_escape_string($id);
    $result = mysql_query($sql_select);
    // erreur renvoyé à helloscan
    if(!$result) {
        sendError(__FUNCTION__,$sql_select);
        exit(0);
    }
    $num_result = mysql_num_rows($result);
    // c'est OK !
    if($num_result==1) {
        return true;
    }
}

// récupérer les informations
function getInfos($id) {
    $sql_select = '
        SELECT * 
        FROM '.HELLO_TABLE.'
        WHERE '.HELLO_FIELD.'='.mysql_escape_string($id).'
        LIMIT 1';
    $result = mysql_query($sql_select);
    // erreur renvoyé à helloscan
    if(!$result) {
        sendError(__FUNCTION__,$sql_select);
        exit(0);
    }
    $data = mysql_fetch_assoc($result);
    mysql_free_result($result);
    if(!empty($data)) {
        return array(
            'status' => 200,
            'result' => 'Informations sur l\'inscrit',
            'data' => $data,
        );
    } else {
        return array(
            'status' => 404,
            'result' => __FUNCTION__.': Impossible de récupérer les informations sur l\'inscrit',
        );
    }
}

// mettre à 1 le champ actif
function updateInfos($id) {
    // requête de mise à jour
    $sql_update = '
        UPDATE '.HELLO_TABLE.'
        SET '.HELLO_ACTIVE_FIELD.'=1
        WHERE '.HELLO_FIELD.'='.mysql_escape_string($id);
    $result = mysql_query($sql_update);
    // erreur renvoyé à helloscan
    if(!$result) {
        return sendError(__FUNCTION__,$sql_update);
    }
    // nombre de ligne affectée
    $num = mysql_affected_rows();
    if($num==1) {
        return array(
            'status' => 200,
            'result' => 'Inscrit validé et actif',
        );
    } else {
        return array(
            'status' => 404,
            'result' => __FUNCTION__.': Impossible de mettre à jour les informations sur l\'inscrit (déjà validé ?)',
        );
    }
}

// vérifier l'authentification
if(empty($_GET['key']) || 
    (!empty($_GET['key']) && $_GET['key']!=HELLO_KEY)) {
    // réponse à HelloScan
    $response = array(
        'status' => 401, // statut http
        'result' => 'Authentification incorrecte',
    );
    echo json_encode($response);
    exit;
}

// vérifier la présence de l'id
if(!empty($_GET['id'])) {
    $id = htmlspecialchars($_GET['id']);
} else {
    // réponse à HelloScan
    $response = array(
        'status' => 404,
        'result' => 'Aucun identifiant reçu',
    );
    echo json_encode($response);
    exit;
}

// vérifier l'id dans la table
if(!checkId($id)) {
    // réponse à HelloScan
    $response = array(
        'status' => 404,
        'msg' => 'Aucun identifiant correspondant dans la table des inscrits',
    );
    echo json_encode($response);
    exit;
}

// renvoyer les informations
if(!empty($_GET['action']) && $_GET['action']=='get') {
    echo json_encode(getInfos($id));
    exit;
}

// mettre à jour le champ actif
if(!empty($_GET['action']) && $_GET['action']=='put') {
    echo json_encode(updateInfos($id));
    exit;
}

// aucune action demandée
$response = array(
    'status' => 404,
    'msg' => 'Aucune action demandée (get ou put)',
);
echo json_encode($response);
exit;
