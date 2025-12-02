<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\WhatsApp\WebhookService;
use App\Services\WhatsApp\MessageService;
use App\Services\WhatsApp\StateService;
use App\Services\WhatsApp\TemplateService;
use App\Services\WhatsApp\AuthService;
use App\Services\WhatsApp\CertificateService;
use App\Services\WhatsApp\UserFlowService;
use App\Actions\WhatsApp\ProcessMessageAction;
use App\Actions\WhatsApp\HandleAuthFlowAction;
use App\Actions\WhatsApp\HandleCertificateFlowAction;

class WhatsAppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Servicios básicos (sin dependencias)
        $this->app->singleton(StateService::class);
        $this->app->singleton(TemplateService::class);
        $this->app->singleton(AuthService::class);
        $this->app->singleton(CertificateService::class);
        
        // MessageService depende de StateService
        $this->app->singleton(MessageService::class, function ($app) {
            return new MessageService($app->make(StateService::class));
        });
        
        // UserFlowService depende de StateService y MessageService
        $this->app->singleton(UserFlowService::class, function ($app) {
            return new UserFlowService(
                $app->make(StateService::class),
                $app->make(MessageService::class)
            );
        });
        
        // Actions
        $this->app->singleton(HandleAuthFlowAction::class, function ($app) {
            return new HandleAuthFlowAction(
                $app->make(MessageService::class),
                $app->make(StateService::class),
                $app->make(TemplateService::class),
                $app->make(AuthService::class)
            );
        });
        
        $this->app->singleton(HandleCertificateFlowAction::class, function ($app) {
            return new HandleCertificateFlowAction(
                $app->make(MessageService::class),
                $app->make(StateService::class),
                $app->make(TemplateService::class),
                $app->make(CertificateService::class)
            );
        });
        
        $this->app->singleton(ProcessMessageAction::class, function ($app) {
            return new ProcessMessageAction(
                $app->make(MessageService::class),
                $app->make(StateService::class),
                $app->make(TemplateService::class),
                $app->make(UserFlowService::class),
                $app->make(HandleAuthFlowAction::class),
                $app->make(HandleCertificateFlowAction::class)
            );
        });
        
        // WebhookService (el que usa el controlador) depende de ProcessMessageAction
        $this->app->singleton(WebhookService::class, function ($app) {
            return new WebhookService($app->make(ProcessMessageAction::class));
        });
    }
}