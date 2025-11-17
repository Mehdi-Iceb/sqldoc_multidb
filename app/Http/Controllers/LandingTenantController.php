<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\TenantCreationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules;
use Inertia\Inertia;

class LandingTenantController extends Controller
{
    /**
     * Affiche la landing page avec le formulaire de création.
     */
    public function create()
    {
        return Inertia::render('Landing'); // Vue Inertia: resources/js/Pages/Landing.vue
    }

    /**
     * Traite l'enregistrement d'un nouveau tenant depuis la landing.
     */
    // public function store(Request $request, TenantCreationService $service)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'name' => 'required|string|max:255',
    //         'slug' => 'required|string|max:50|unique:domains,domain|alpha_dash',
    //         'contact_name' => 'required|string|max:255',
    //         'contact_email' => 'required|email|max:255',
    //         'admin_password' => ['required', 'confirmed', Rules\Password::defaults()],
    //     ]);

    //     if ($validator->fails()) {
    //         return back()->withErrors($validator)->withInput();
    //     }

    //     try {
    //         // Préparation des données
    //         $data = $validator->validated();
    //         $data['type'] = 'private'; // Forcément privé via landing
    //         $data['country'] = 'FR'; // Valeur par défaut
    //         $data['data'] = ['created_from' => 'landing'];

    //         // Création via le service
    //         $tenant = $service->createTenant($data);

    //         // Redirection vers le sous-domaine du tenant
    //         $domain = $tenant->domains->first()->domain;

    //         return redirect()->away("https://{$domain}/login")
    //             ->with('success', "Votre espace a été créé avec succès !");
    //     } catch (\Throwable $e) {
    //         return back()->withInput()->withErrors([
    //             'error' => 'Erreur : ' . $e->getMessage(),
    //         ]);
    //     }
    // }
}


