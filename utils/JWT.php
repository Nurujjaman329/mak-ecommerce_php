<?php
// utils/JWT.php
class JWT {
    private static $secret = '';

    public static function setSecret($secret) {
        self::$secret = $secret;
    }

    public static function encode($payload, $exp = 604800) { // 7 days default
        $header = ['alg'=>'HS256','typ'=>'JWT'];
        $payload['exp'] = time() + $exp;

        $base64UrlHeader = self::base64UrlEncode(json_encode($header));
        $base64UrlPayload = self::base64UrlEncode(json_encode($payload));
        $signature = hash_hmac('sha256', "$base64UrlHeader.$base64UrlPayload", self::$secret, true);
        $base64UrlSignature = self::base64UrlEncode($signature);

        return "$base64UrlHeader.$base64UrlPayload.$base64UrlSignature";
    }

    public static function decode($jwt) {
        $parts = explode('.', $jwt);
        if(count($parts) !== 3) return false;

        list($header64, $payload64, $signature64) = $parts;
        $signature = self::base64UrlDecode($signature64);
        $validSig = hash_hmac('sha256', "$header64.$payload64", self::$secret, true);

        if(!hash_equals($signature, $validSig)) return false;

        $payload = json_decode(self::base64UrlDecode($payload64), true);
        if($payload['exp'] < time()) return false;

        return $payload;
    }

    private static function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode($data) {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $padlen = 4 - $remainder;
            $data .= str_repeat('=', $padlen);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
