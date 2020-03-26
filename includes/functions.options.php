<?php

function option_exists($name)
{
    /**
     * @var PDO $dbh
     */
    global $dbh;

    if (!empty($dbh)) {
        $get = $dbh->prepare("SELECT name FROM " . TABLE_OPTIONS . " WHERE name=:name");
        $get->bindParam(':name', $name);
        $get->execute();
        $row = $get->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return true;
        }
    }

    return false;
}

function get_option($name)
{
    global $dbh;

    if (!empty($dbh)) {
        $get = $dbh->prepare("SELECT value FROM " . TABLE_OPTIONS . " WHERE name=:name");
        $get->bindParam(':name', $name);
        $get->execute();
        $row = $get->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return $row['value'];
        }
    }

    return false;
}

function save_option($name, $value)
{
    global $dbh;

    if (!empty($dbh)) {
        if (option_exists($name)) {
            $save = $dbh->prepare("UPDATE " . TABLE_OPTIONS . " SET value=:value WHERE name=:name");
            $save->bindParam(':value', $value);
            $save->bindParam(':name', $name);
            return $save->execute();
        } else {
            $save = $dbh->prepare(
                "INSERT INTO " . TABLE_OPTIONS . " (name, value)"
                . " VALUES (:name, :value)"
            );
            $save->bindParam(':name', $name);
            $save->bindParam(':value', $value);
            return $save->execute();
        }
    }

    return false;
}
