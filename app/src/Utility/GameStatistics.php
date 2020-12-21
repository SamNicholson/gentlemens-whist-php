<?php


namespace App\Utility;


class GameStatistics
{
    /**
     * @var Database
     */
    private static $database;

    /**
     * @param Database $database
     */
    public static function setDatabase(Database $database)
    {
        self::$database = $database;
    }

    public static function getPlayerStats()
    {
        return self::$database->q(
            "
                SELECT name AS player,
                    (SELECT count(*) FROM games AS g 
                        LEFT JOIN games_players gp on g.id = gp.game_id
                        LEFT JOIN players p on gp.player_id = p.id
                        WHERE gp.player_id = pp.id
                    ) AS totalGames,
                    (SELECT AVG(gs.score) FROM games AS g 
                        LEFT JOIN games_players gp on g.id = gp.game_id
                        LEFT JOIN players p on gp.player_id = p.id
                        LEFT JOIN games_scores gs on g.id = gs.game_id
                        WHERE gs.player = pp.id
                    ) AS averageScore,
                    (SELECT MAX(gs.score) FROM games AS g 
                        LEFT JOIN games_players gp on g.id = gp.game_id
                        LEFT JOIN players p on gp.player_id = p.id
                        LEFT JOIN games_scores gs on g.id = gs.game_id
                        WHERE gs.player = pp.id
                    ) AS maximumScore,
                    (SELECT MIN(gs.score) FROM games AS g 
                        LEFT JOIN games_players gp on g.id = gp.game_id
                        LEFT JOIN players p on gp.player_id = p.id
                        LEFT JOIN games_scores gs on g.id = gs.game_id
                        WHERE gs.player = pp.id
                    ) AS minimumScore,
                    (SELECT ROUND(SUM(
                            (SELECT IF(gs.score = MAX(sgs.score), 1, 0) FROM games_scores AS sgs WHERE sgs.game_id = gs.game_id)
                        ) / 3, 0) FROM games AS g 
                        LEFT JOIN games_players gp on g.id = gp.game_id
                        LEFT JOIN players p on gp.player_id = p.id
                        LEFT JOIN games_scores gs on g.id = gs.game_id
                        WHERE gs.player = pp.id
                    ) AS firstPlaces,
                    (SELECT ROUND(SUM(
                            (SELECT IF(gs.score != MIN(sgs.score) and gs.score != MAX(sgs.score), 1, 0) FROM games_scores AS sgs WHERE sgs.game_id = gs.game_id)
                        ) / 3, 0) FROM games AS g 
                        LEFT JOIN games_players gp on g.id = gp.game_id
                        LEFT JOIN players p on gp.player_id = p.id
                        LEFT JOIN games_scores gs on g.id = gs.game_id
                        WHERE gs.player = pp.id
                    ) AS secondPlaces,
                    (SELECT ROUND(SUM(
                            (SELECT IF(gs.score = MIN(sgs.score), 1, 0) FROM games_scores AS sgs WHERE sgs.game_id = gs.game_id)
                        ) / 3, 0) FROM games AS g 
                        LEFT JOIN games_players gp on g.id = gp.game_id
                        LEFT JOIN players p on gp.player_id = p.id
                        LEFT JOIN games_scores gs on g.id = gs.game_id
                        WHERE gs.player = pp.id
                    ) AS thirdPlaces
                       
                       
                       FROM players AS pp
                "
        );
    }
}