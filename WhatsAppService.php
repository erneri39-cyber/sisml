<?php

class WhatsAppService
{
    public function sendFileMessage(string $phone, string $message, string $filePath, string $fileName): void
    {
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($cleanPhone) === 8) {
            $cleanPhone = '503' . $cleanPhone; // Código de El Salvador
        }

        $log = "[WhatsApp SIMULADO] Enviando a $cleanPhone:\n$message\nArchivo: $fileName";
        error_log($log);
    }
}