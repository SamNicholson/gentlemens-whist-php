<?php


namespace App\Utility;


class DataRequest
{
    private static $database;

    /**
     * @param Database $database
     */
    public static function setDatabase(Database $database)
    {
        self::$database = $database;
    }

    public static function getAllPlayers()
    {
        return self::$database->q(
            "SELECT * FROM players"
        );
    }

    public static function getActivePlayer()
    {
        return self::$database->queryRow(
            "SELECT * FROM players WHERE id = ?",
            [
                $_SESSION['user']
            ]
        );
    }

    public static function getCompleteGames()
    {
        return self::$database->q(
            "SELECT games.id, games.name, GROUP_CONCAT(DISTINCT games_players.nickname ORDER BY games_players.player_id SEPARATOR ', ') AS playerList, games.start_time FROM games
                    LEFT JOIN games_players ON games.id = games_players.game_id
                    LEFT JOIN players ON players.id = games_players.player_id 
                    WHERE completed = 1
                    GROUP BY games.id
                    ORDER BY games.id DESC
                    "
        );
    }

    public static function getNonCompleteGames()
    {
        return self::$database->q(
            "SELECT games.id, games.name, GROUP_CONCAT(DISTINCT games_players.nickname ORDER BY games_players.player_id SEPARATOR ', ') AS playerList, games.start_time FROM games
                    LEFT JOIN games_players ON games.id = games_players.game_id
                    LEFT JOIN players ON players.id = games_players.player_id 
                    WHERE (completed = 0 OR completed IS NULL)
                    GROUP BY games.id
                    ORDER BY games.id DESC
                    "
        );
    }
}