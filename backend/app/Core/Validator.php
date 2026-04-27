<?php

class Validator {

    public static function email($value): bool {
        return preg_match("/^[\w\.-]+@[\w\.-]+\.\w{2,}$/", $value);
    }

    public static function password($value): bool {
        return preg_match("/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[\W_]).{8,}$/", $value);
    }

    public static function name($value): bool {
        return preg_match("/^[a-zA-Z\s]{2,50}$/", $value);
    }
}