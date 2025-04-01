<?php

namespace App\Enum;

enum ExecutionStatus: string
{
    //#region Constantes
    /**
     * Status d'attente
     * 
     * Ce status indique que l'exécution est 
     * en attente d'un participant.
     * 
     * @since 1.0.0
     * 
     * @var string WAITING Status d'attente
     */
    case WAITING = 'waiting';

    /**
     * Status de démarrage
     * 
     * Ce status indique que l'exécution 
     * a démarré.
     * 
     * @since 1.0.0
     * 
     * @var string STARTED Status de démarrage
     */
    case STARTED = 'started';

    /**
     * Status de fin
     * 
     * Ce status indique que l'exécution 
     * est terminée.
     * 
     * @since 1.0.0
     * 
     * @var string ENDED Status de fin
     */
    case ENDED = 'ended';
    //#endregion

    //#region Méthodes
    /**
     * Méthode values
     * @static
     * 
     * Cette méthode permet de récupérer les valeurs
     * de l'énumération sous forme de tableau.
     * 
     * @access public
     * @since 1.0.0
     * 
     * @return array<string> Tableau des valeurs de l'énumération
     */
    public static function values(): array 
    {
        return array_column(
            array: self::cases(), 
            column_key: 'value'
        );
    }
    //#endregion
}