<?php
// Test rapide pour vérifier que les templates d'emails existent

$templates = [
    'templates/emails/booking_confirmed_client.html.twig',
    'templates/emails/booking_confirmed_stylist.html.twig',
    'templates/emails/booking_cancelled_client.html.twig',
    'templates/emails/booking_cancelled_stylist.html.twig',
];

echo "=== VÉRIFICATION DES TEMPLATES D'EMAILS ===\n\n";

foreach ($templates as $template) {
    if (file_exists($template)) {
        echo "✅ {$template} - Existe\n";
        $content = file_get_contents($template);
        $lines = substr_count($content, "\n") + 1;
        echo "   Taille: " . strlen($content) . " caractères, {$lines} lignes\n";
    } else {
        echo "❌ {$template} - N'existe pas\n";
    }
}

echo "\n=== VÉRIFICATION TERMINÉE ===\n";
?>
