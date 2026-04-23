<?php

namespace KaiPfeiffer\WPBase;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @class JWT_Singleton
 * 
 * @author  Kai Pfeiffer <kp@loworx.com>
 * @package rideshare
 * @since   1.0.0 
 */
class JWTSingleton
{
    use SingletonTrait;

    /**
     * $algorithm.
     *
     * Der Algorithmus, mit dem der Hash des Tokens verschlüsselt wird
     * 
     * @since    1.0.0
     */
    protected $algorithm = 'sha512';


    /**
     * Die Instanz der Klasse.
     *
     * Die Instanz-Variable muss in jedem Child-Element neu 
     * definiert werden, damit jes Singleton mit seiner eigenen Instanz
     * arbeitet
     * 
     * @since    1.0.0
     */
    protected static $instance = null;


    /**
     * subject for jwt token
     */
    protected $subject  = 'rideshare';


    /**
     * $secret.
     *
     * Der Algorithmus, mit dem der Hash des Tokens verschlüsselt wird
     * 
     * @since    1.0.0
     */
    protected $secret;


    /**
     * Die Klasse.
     *
     * Die Klasse muss in jedem Child-Element neu mit der magischen 
     * Konstanten __CLASS__ belegt werden, damit bei den statischen
     * Methoden die richtigen Referenzierungen genutzt werden.
     * 
     * @var string
     * 
     * @since    1.0.0
     */
    protected static $self      = __CLASS__;


    /**
     * $issuer.
     *
     * Der Anbieter des Dienstes
     * 
     * @since    1.0.0
     */
    protected $issuer;


    /**
     * decode
     * 
     * Kodiert den übergebenen String in base64
     * 
     * @param   string
     * @since    1.0.0
     */
    protected function decode($data)
    {
        return base64_decode($data);
    }


    /**
     * encode
     * 
     * Kodiert den übergebenen String in base64
     * 
     * @param   string
     * @since    1.0.0
     */
    protected function encode($data)
    {
        // return base64_encode($data);
        // Das str_replace macht das Token URL-kompatibel
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }


    /**
     * get_issuer
     * 
     * gibt den Herausgeber des Tokens zurück
     * 
     * @return  string
     * @since    1.0.0
     */
    protected function get_issuer()
    {
        if (!$this->issuer) {
            $this->issuer = $_SERVER['SERVER_NAME'];
        }
        return $this->issuer;
    }


    /**
     * get_secret
     * 
     * Falls Wordpress-Konstanten für Keys gesetzt sind, diese zum
     * verschlüsseln des Hashes nutzen
     * 
     * @return  string
     * @since    1.0.0
     */
    protected function get_secret()
    {
        if (!$this->secret) {
            if (defined('\SECURE_AUTH_SALT')) {
                $secret = \SECURE_AUTH_SALT;
            }
            if (defined('\SECURE_AUTH_KEY')) {
                $secret .= \SECURE_AUTH_KEY;
            }
            $this->secret   = $secret;
        }
        return $this->secret;
    }


    /**
     * decode_jwt
     * 
     * erzeugt ein JSON-Web-Token mit den übermittelten Daten
     * 
     * @param   array   die zu speichernden Daten
     * @param   integer die Gültigkeits-Dauer des Tokens, default 1 Tag
     * @since    1.0.0
     */
    public function decode_jwt($jwt)
    {
        $result     = null;
        list($base64UrlHeader, $base64UrlPayload, $base64UrlSignature)    = explode('.', $jwt);

        $json_header     = $this->decode($base64UrlHeader);
        $json_payload    = $this->decode($base64UrlPayload);
        $signature       = $this->decode($base64UrlSignature);

        if ($json_header && $header = json_decode($json_header)) {
            $algorithm  = isset($header->alg) ? $header->alg : $this->algorithm;
        }

        // Schlüssel auslesen
        $secret = $this->get_secret();

        if ($signature === hash_hmac($algorithm, $base64UrlHeader . '.' . $base64UrlPayload, $secret)) {
            $payload    = json_decode($json_payload);
            if ($payload->exp > time()) {
                $result = $payload->data;
            }
        }

        return $result;
    }


    /**
     * generate_jwt
     * 
     * erzeugt ein JSON-Web-Token mit den übermittelten Daten
     * 
     * @param   array   die zu speichernden Daten
     * @param   integer die Gültigkeits-Dauer des Tokens, default 1 Tag
     * @since    1.0.0
     */
    public function generate_jwt($data, $validity = 60 * 60 * 24)
    {
        // Generierungsdatum
        $iat = time();

        $payload    = array(
            'data'  => $data,
            'id'    => uniqid($this->issuer, true), // Unique ID
            'sub'   => $this->subject,                 // Subject
            'exp'   => $iat + $validity,            // Expiration date
            'iss'   => $this->get_issuer(),         // issuer
            'iat'   => $iat,                        // issued at
        );

        // Create token header as a JSON string
        $json_header = json_encode(['typ' => 'JWT', 'alg' => $this->algorithm]);

        // Create token payload as a JSON string
        $json_payload = json_encode($payload);

        // Encode Header to Base64Url String
        $base64UrlHeader = $this->encode($json_header);

        // Encode Payload to Base64Url String
        $base64UrlPayload = $this->encode($json_payload);

        // Schlüssel auslesen
        $secret = $this->get_secret();

        // Create Signature Hash
        $signature = hash_hmac($this->algorithm, $base64UrlHeader . '.' . $base64UrlPayload, $secret);

        // Encode Signature to Base64Url String
        $base64UrlSignature = $this->encode($signature);

        // Create JWT
        $jwt = $base64UrlHeader . '.' . $base64UrlPayload . '.' . $base64UrlSignature;

        return $jwt;
    }
}
