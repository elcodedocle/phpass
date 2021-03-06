<?php
/** 
 * Portable PHP password hashing framework.
 *
 * Version 0.3 / SynappV2 (by elcodedocle, 2014).
 *
 * Original written by Solar Designer <solar at openwall.com> in 2004-2006 and placed in
 * the public domain.  Revised in subsequent years, still public domain.
 *
 * There's absolutely no warranty.
 *
 * The homepage URL for the original framework is:
 *
 *    http://www.openwall.com/phpass/
 *
 * Please be sure to update the Version line if you edit this file in any way.
 * It is suggested that you leave the main version number intact, but indicate
 * your project name (after the slash) and add your own revision information.
 *
 * Please do not change the "private" password hashing method implemented in
 * here, thereby making your hashes incompatible.  However, if you must, please
 * change the hash type identifier (the "$P$") to something different.
 *
 * Obviously, since this code is in the public domain, the above are not
 * requirements (there can be none), but merely suggestions.
 *
 * elcodedocle's rev: I've ignored Solar Designer's recommendation but maintained full
 * compatibility. Compatibility with future phpass versions is not warranted, though.
 * 
 * Latest version and revision notes available at https://github.com/elcodedocle/phpass
 */

class PasswordHash {
    var $itoa64;
    var $iteration_count_log2;
    var $portable_hashes;
    var $random_state;
    var $hash_method; // do not modify directly, use set_hash_method instead.
    /**
     * Old time constructor. It sets defaults and properties.
     * 
     * Supported hash methods are (ordered by decreasing strength, increasing speed and old PHP versions compatibility):
     *
     * password_hash (requires portable_hashes set to false, plus PHP 5 >= 5.5.0)
     * crypt_blowfish (requires portable_hashes set to false, plus PHP 5 >= 5.3.0 or system support)
     * crypt_extended_DES (requires portable_hashes set to false, plus PHP 5 >= 5.3.0 or system support)
     * private_sha512  (requires PHP 5 >= 5.1.2, PECL hash >= 1.1)
     * private_sha256 (requires PHP 5 >= 5.1.2, PECL hash >= 1.1)
     * private_md5 (requires PHP 4, PHP 5)
     * 
     * var $hash_method is a private property (unfortunately in PHP 4 there is no such thing) and therefore should not be modified directly,
     * but using the provided method: set_hash_method.
     * 
     * $full_compat sets md5 instead of password_hash or sha when no method is specified and stronger methods are not available.
     * 
     * @param int $iteration_count_log2 (defaults to 9) number of iterations for the hashing algo on the implemented 'crypt_private' method
     * @param bool $portable_hashes (defaults to false) wheter to use only portable hashes implemented on this class 'crypt_private' method
     * @param mixed $hash_method (defaults to null) string specifying password hashing method. null sets the strongest method available.
     * @param bool $full_compat (defaults to true), whether or not to maintain full backwards compatibility with phpass 0.3 by Solar Designer.
     * @return mixed false on fail, the class instance on success
     */
    function PasswordHash($iteration_count_log2 = 9, $portable_hashes = false, $hash_method = null, $full_compat = true)
    {
        $this->portable_hashes = $portable_hashes;
        $this->full_compat = $full_compat;
        
        if ($this->set_hash_method($hash_method)===false){
            return false;
        }
        $this->itoa64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

        if ($iteration_count_log2 < 4 || $iteration_count_log2 > 31)
            $iteration_count_log2 = 8;
        $this->iteration_count_log2 = $iteration_count_log2;


        $this->random_state = microtime();
        if (function_exists('getmypid'))
            $this->random_state .= getmypid();
        
        return $this;
    }
    function is_valid_hash_method($hash_method, $portable_hashes = false){
        switch ($hash_method){
            case 'password_hash':
                if (function_exists('password_hash') && !$portable_hashes){
                    return true;
                } else { return false; }
            case 'crypt_blowfish':
                if (CRYPT_BLOWFISH && !$portable_hashes){
                    return true;
                } else { return false; }
            case 'crypt_extended_DES':
                if (CRYPT_EXT_DES && !$portable_hashes){
                    return true;
                } else { return false; }
            case 'private_sha512':
                if (function_exists('hash')){
                    return true;
                } else { return false; }
            case 'private_sha256':
                if (function_exists('hash')){
                    return true;
                } else { return false; }
            case 'private_md5':
                return true;
            default:
                return false;
        }
    }
    /**
     * Sets the password hashing method to use by the class
     * 
     * Available methods are (ordered by increasing strength, decreasing speed and old PHP versions compatibility):
     *
     * password_hash (requires portable_hashes set to false, plus PHP 5 >= 5.5.0)
     * crypt_blowfish (requires portable_hashes set to false, plus PHP 5 >= 5.3.0 or system support)
     * crypt_extended_DES (requires portable_hashes set to false, plus PHP 5 >= 5.3.0 or system support)
     * private_sha512  (requires PHP 5 >= 5.1.2, PECL hash >= 1.1)
     * private_sha256 (requires PHP 5 >= 5.1.2, PECL hash >= 1.1)
     * private_md5 (requires PHP 4, PHP 5)
     * 
     * @param mixed $hash_method (defaults to null) a string containing the desired hash method. null will set the strongest available.
     * @return bool true on success, false on error
     */
    function set_hash_method($hash_method = null){
        if ($hash_method === null){
            if (function_exists('password_hash') && !$this->full_compat){
                $this->hash_method = 'password_hash';
            } else if (CRYPT_BLOWFISH){
                $this->hash_method = 'crypt_blowfish';
            } else if (CRYPT_EXT_DES){
                $this->hash_method = 'crypt_extended_DES';
            } else if (function_exists('hash') && !$this->full_compat){
                $this->hash_method = 'private_sha512';
            } else {
                $this->hash_method= 'private_md5';
            }
        } else {
            if ($this->is_valid_hash_method($hash_method)){
                $this->hash_method = $hash_method;
            } else {
                return false;
            }
        }
        return true;
    }
    function get_random_bytes($count)
    {
        $output = '';
        if (is_readable('/dev/urandom') &&
            ($fh = @fopen('/dev/urandom', 'rb'))) {
            $output = fread($fh, $count);
            fclose($fh);
        }

        if (strlen($output) < $count) {
            $output = '';
            for ($i = 0; $i < $count; $i += 16) {
                $this->random_state =
                    md5(microtime() . $this->random_state);
                $output .=
                    pack('H*', md5($this->random_state));
            }
            $output = substr($output, 0, $count);
        }

        return $output;
    }
    function encode64($input, $count)
    {
        $output = '';
        $i = 0;
        do {
            $value = ord($input[$i++]);
            $output .= $this->itoa64[$value & 0x3f];
            if ($i < $count)
                $value |= ord($input[$i]) << 8;
            $output .= $this->itoa64[($value >> 6) & 0x3f];
            if ($i++ >= $count)
                break;
            if ($i < $count)
                $value |= ord($input[$i]) << 16;
            $output .= $this->itoa64[($value >> 12) & 0x3f];
            if ($i++ >= $count)
                break;
            $output .= $this->itoa64[($value >> 18) & 0x3f];
        } while ($i < $count);

        return $output;
    }
    function gensalt_private($input, $hash_method = null)
    {
        if ($hash_method === null){
            $hash_method = $this->hash_method;
        }
        switch($hash_method){
            case 'private_sha512': $output = '$S$'; break;
            case 'private_sha256': $output = '$Z$'; break;
            case 'private_md5': $output = '$P$'; break;
            default : 
                if ($this->portable_hashes===false){
                    return '*0';
                } else if (!$this->full_compat && $this->is_valid_hash_method('private_sha512')){
                    $output = '$S$';
                } else if (!$this->full_compat && $this->is_valid_hash_method('private_sha256')){
                    $output = '$Z$';
                } else { $output = '$P$'; } // private_md5
        }  
        $output .= $this->itoa64[min($this->iteration_count_log2 +
            ((PHP_VERSION >= '5') ? 5 : 3), 30)];
        $output .= $this->encode64($input, 6);

        return $output;
    }
    function crypt_private($password, $setting)
    {
        $output = '*0';
        if (substr($setting, 0, 2) == $output)
            $output = '*1';

        $id = substr($setting, 0, 3);
        # We use "$P$", phpBB3 uses "$H$" for the same thing
        if ($id != '$S$' && $id != '$Z$' && $id != '$P$' && $id != '$H$')
            return $output;

        $count_log2 = strpos($this->itoa64, $setting[3]);
        if ($count_log2 < 7 || $count_log2 > 30)
            return $output;

        $count = 1 << $count_log2;

        $salt = substr($setting, 4, 8);
        if (strlen($salt) != 8)
            return $output;

        # We're kind of forced to use MD5 here since it's the only
        # cryptographic primitive available in all versions of PHP
        # currently in use.  To implement our own low-level crypto
        # in PHP would result in much worse performance and
        # consequently in lower iteration counts and hashes that are
        # quicker to crack (by non-PHP code).
        
        # elcodedocle's note: I'm overriding that and assuming if
        # you explicitly set hash_method to other than 'private_md5'
        # you know what you're doing.
        if ($id === '$S$' || $id === '$Z$'){
            $hash_func = ($id === '$S$')?'sha512':'sha256';
            if (!function_exists('hash')){
                return '*2';
            }
            $hash = hash($hash_func, $salt . $password, TRUE);
            do {
                $hash = hash($hash_func, $hash . $password, TRUE);
            } while (--$count);
        } else {
            if (PHP_VERSION >= '5') {
                $hash = md5($salt . $password, TRUE);
                do {
                    $hash = md5($hash . $password, TRUE);
                } while (--$count);
            } else {
                $hash = pack('H*', md5($salt . $password));
                do {
                    $hash = pack('H*', md5($hash . $password));
                } while (--$count);
            }
        }

        $output = substr($setting, 0, 12);
        $output .= $this->encode64($hash, 16);

        return $output;
    }
    function gensalt_extended($input)
    {
        $count_log2 = min($this->iteration_count_log2 + 8, 24);
        # This should be odd to not reveal weak DES keys, and the
        # maximum valid value is (2**24 - 1) which is odd anyway.
        $count = (1 << $count_log2) - 1;

        $output = '_';
        $output .= $this->itoa64[$count & 0x3f];
        $output .= $this->itoa64[($count >> 6) & 0x3f];
        $output .= $this->itoa64[($count >> 12) & 0x3f];
        $output .= $this->itoa64[($count >> 18) & 0x3f];

        $output .= $this->encode64($input, 3);

        return $output;
    }
    function gensalt_blowfish($input)
    {
        # This one needs to use a different order of characters and a
        # different encoding scheme from the one in encode64() above.
        # We care because the last character in our encoded string will
        # only represent 2 bits.  While two known implementations of
        # bcrypt will happily accept and correct a salt string which
        # has the 4 unused bits set to non-zero, we do not want to take
        # chances and we also do not want to waste an additional byte
        # of entropy.
        $itoa64 = './ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

        $output = '$2a$';
        $output .= chr(ord('0') + $this->iteration_count_log2 / 10);
        $output .= chr(ord('0') + $this->iteration_count_log2 % 10);
        $output .= '$';

        $i = 0;
        do {
            $c1 = ord($input[$i++]);
            $output .= $itoa64[$c1 >> 2];
            $c1 = ($c1 & 0x03) << 4;
            if ($i >= 16) {
                $output .= $itoa64[$c1];
                break;
            }

            $c2 = ord($input[$i++]);
            $c1 |= $c2 >> 4;
            $output .= $itoa64[$c1];
            $c1 = ($c2 & 0x0f) << 2;

            $c2 = ord($input[$i++]);
            $c1 |= $c2 >> 6;
            $output .= $itoa64[$c1];
            $output .= $itoa64[$c2 & 0x3f];
        } while (1);

        return $output;
    }
    /**
     * Hashes the provided password using the specified method when provided
     * 
     * @param string $password The password string to be hashed
     * @param mixed $hash_method (optional, defaults to null) string specifying the hashing method; when null, $this->hash_method is used
     * @return bool|string A string containing the hash or false on error. (may also return a crypt private error string: '*'.$err_num)
     */
    function HashPassword($password, $hash_method = null)
    {
        if ($hash_method === null){
            $hash_method = $this->hash_method;
        } else if (!$this->is_valid_hash_method($hash_method)){
            return false;
        }
        if ($hash_method === 'password_hash' && !$this->portable_hashes){
            return password_hash($password, PASSWORD_DEFAULT);
        }
        $random = '';

        if ($hash_method === 'crypt_blowfish' && !$this->portable_hashes) {
            $random = $this->get_random_bytes(16);
            $hash =
                crypt($password, $this->gensalt_blowfish($random));
            if (strlen($hash) == 60)
                return $hash;
        }

        if ($hash_method === 'crypt_extended_DES' && !$this->portable_hashes) {
            if (strlen($random) < 3)
                $random = $this->get_random_bytes(3);
            $hash =
                crypt($password, $this->gensalt_extended($random));
            if (strlen($hash) == 20)
                return $hash;
        }

        if (strlen($random) < 6)
            $random = $this->get_random_bytes(6);
        $hash =
            $this->crypt_private($password,
                $this->gensalt_private($random, $hash_method));
        if (strlen($hash) == 34 || strlen($hash) == 98 || strlen($hash) == 130){
            return $hash;
        } else { return '*'; } // This is bad, and Solar Designer should feel bad.
    }

    /**
     * Checks the provided password against the provided hash
     * 
     * Returns true on match, false otherwise
     * 
     * @param string $password the password to check against the provided stored hash
     * @param string $stored_hash the hash to be checked against the provided password
     * @return bool true if the password matches the hash, false if it doesn't
     */
    function CheckPassword($password, $stored_hash)
    {
        $id = substr($stored_hash, 0, 3);
        if ($id != '$S$' && $id != '$Z$' && $id != '$P$' && $id != '$H$'){
            if ($this->hash_method === 'password_hash'){
                return (password_verify($password, $stored_hash));
            }
        }
        $hash = $this->crypt_private($password, $stored_hash);
        if ($hash[0] == '*')
            $hash = crypt($password, $stored_hash);

        return $hash == $stored_hash;
    }
}

