<?php

class FrontendController
{
    /**
     * Abre a tela visual de listagem e gerenciamento de Pessoas
     */
    public function pessoas(): void
    {
        // Define a URL base caso não esteja centralizada em outro arquivo
        $baseUrl = '/atendelab/public/';
        
        // Inclui a View correspondente
        require __DIR__ . '/../Views/pessoas/index.php';
    }

    /**
     * Abre a tela visual de categorias de Tipos de Atendimento
     */
    public function tiposAtendimentos(): void
    {
        $baseUrl = '/atendelab/public/';
        require __DIR__ . '/../Views/tipos-atendimentos/index.php';
    }

    /**
     * Abre a tela visual de Registro e Acompanhamento de Atendimentos
     */
    public function atendimentos(): void
    {
        $baseUrl = '/atendelab/public/';
        require __DIR__ . '/../Views/atendimentos/index.php';
    }
}