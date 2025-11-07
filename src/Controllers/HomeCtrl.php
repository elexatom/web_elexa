<?php

namespace App\Controllers;

use App\Models\UserManager;

class HomeCtrl extends Controller
{

    public function process(array $params): void
    {
        // TODO: tady to je testovaci, pak predelat az budou clanky
        $usrMngr = new UserManager();
        $users = $usrMngr->getAllUsers();
        //print_r($users);
        $this->header['title'] = "Forum DigiArch"; // hlavicka stranky
        $this->view = 'home'; // sablona
        $this->data['recentArticles'] = $users;
        $this->data['user'] = $_SESSION['user'] ?? ''; // data o uzivateli

            /*[
            // TODO: tady to musi dolezt z DB
            /*
            ['title' => 'Card 1', 'desc' => 'A card component has a figure, a body part, and inside body there are title and actions parts'],
            ['title' => 'Card 2', 'desc' => 'Another one'],
            ['title' => 'Card 3', 'desc' => 'A title and accard compo a figurnside boe, a body nent haspart, and idy there are tions partse'],
            ['title' => 'hell nah', 'desc' => 'caroe, a body nent haspart, aA title and acnd idy thered compo a figurnside b are tions partse'],
            ['title' => 'blah blah', 'desc' => 'A ti accard compo a f partsigurnside boe, a btle andody nent haspart, and idy there are tionse'],

        ];*/
    }
}