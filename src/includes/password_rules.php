<?php

const PASSWORD_HINT = "At least 8 characters, including one uppercase letter, one number, and one special character.";

/**
 * Returns an error message if the password fails the complexity rule, or null if valid.
 */
function validate_password_strength(string $password): ?string
{
    if (strlen($password) < 8) {
        return "Password must be at least 8 characters";
    }
    if (!preg_match('/[A-Z]/', $password)) {
        return "Password must include at least one uppercase letter";
    }
    if (!preg_match('/[0-9]/', $password)) {
        return "Password must include at least one number";
    }
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        return "Password must include at least one special character";
    }
    return null;
}
?>
