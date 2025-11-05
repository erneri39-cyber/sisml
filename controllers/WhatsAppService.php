<?php
/**
 * Servicio para simular el envío de mensajes y archivos por WhatsApp.
 * En un entorno de producción, se reemplazaría con una API real (ej: Twilio, Meta).
 */
class WhatsAppService
{
    /**
     * Simula el envío de un mensaje con un archivo adjunto.
     *
     * @param string $recipientPhoneNumber El número de teléfono del destinatario.
     * @param string $message El cuerpo del mensaje.
     * @param string $filePath La ruta al archivo PDF que se adjuntará.
     * @param string $fileName El nombre que tendrá el archivo adjunto.
     * @return bool True si el envío fue "exitoso", false en caso contrario.
     */
    public function sendFileMessage(string $recipientPhoneNumber, string $message, string $filePath, string $fileName): bool
    {
        if (!file_exists($filePath)) {
            error_log("WhatsAppService Error: El archivo a enviar no existe en la ruta: $filePath");
            return false;
        }

        // SIMULACIÓN: En lugar de enviar, registramos la acción en el log de errores de PHP.
        error_log("WhatsAppService SIMULACIÓN: Enviando a {$recipientPhoneNumber}. Mensaje: '{$message}'. Archivo: {$fileName}.");
        return true;
    }
}