<?php

namespace App\Tests\E2E;

/**
 * Classe UserE2ETest
 * @final
 * 
 * Cette classe permet de tester le 
 * contrôleur UserController.
 * 
 * @package App\Tests\E2E
 * @category End-to-End Tests
 * 
 * @version 1.0.0
 * 
 * @author Valentin FORTIN <valentin.fortin@ynov.com>
 */
final class UserE2ETest extends FirebaseTestCase
{
    //#region Constantes
    /**
     * Constante USER_ENDPOINT
     * 
     * Chemin de l'API pour les 
     * utilisateurs
     * 
     * @access private
     * @since 1.0.0
     * 
     * @var string USER_ENDPOINT Chemin de l'API pour les utilisateurs
     */
    private const USER_ENDPOINT = '/api/users';
    //#endregion

    //#region Propriétés
    /**
     * Propriété userId
     * 
     * Identifiant de l'utilisateur
     * 
     * @access private
     * @since 1.0.0
     * 
     * @var string|null $userId Identifiant de l'utilisateur
     */
    private static ?string $userId = null;
    //#endregion

    //#region Méthodes
    /**
     * Méthode testRegisterUser
     * 
     * Cette méthode permet de tester l'inscription 
     * d'un utilisateur.
     * 
     * @access public
     * @since 1.0.0
     * 
     * @return void Ne retourne rien
     */
    public function testRegisterUser(): void
    {
        $this->markTestIncomplete(
            message: 'This test has not been implemented yet.'
        );
    }
    //#endregion
}