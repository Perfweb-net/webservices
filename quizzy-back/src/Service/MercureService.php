<?php

namespace App\Service;
class MercureService
{
    private $connectedParticipants = 0;

// Ajouter un participant
    public function addParticipant()
    {
        $this->connectedParticipants++;
    }

// Supprimer un participant
    public function removeParticipant()
    {
        $this->connectedParticipants--;
    }

// Récupérer le nombre de participants connectés
    public function getParticipantsCount(): int
    {
        return $this->connectedParticipants;
    }
}
