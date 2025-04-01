<?php

function hash_password($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

function verify_password($password, $hashedPassword) {
    return password_verify($password, $hashedPassword);
}

?>
