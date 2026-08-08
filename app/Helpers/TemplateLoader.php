<?php
declare(strict_types=1);

namespace HindBoutik\Helpers;

class TemplateLoader
{
    private string $templateDir;

    public function __construct()
    {
        $this->templateDir = HINDBOUTIK_CORE_DIR . 'templates/';
    }

    /**
     * Render a template with extracted variables.
     *
     * @param string $template  e.g. 'size-guide-modal'
     * @param array  $vars      Variables passed to the template
     * @return string           Rendered HTML
     */
    public function render(string $template, array $vars = []): string
    {
        $templateFile = $this->getTemplatePath($template);

        if (!file_exists($templateFile)) {
            return '';
        }

        // Extract variables into local scope.
        extract($vars, EXTR_SKIP);

        ob_start();
        include $templateFile;
        return (string) ob_get_clean();
    }

    /**
     * Get the full path to a template, checking for theme overrides first.
     */
    public function getTemplatePath(string $template): string
    {
        $template .= '.php';

        // Check theme override first.
        $themeOverride = get_stylesheet_directory() . '/hindboutik-core/' . $template;
        if (file_exists($themeOverride)) {
            return $themeOverride;
        }

        // Fall back to plugin template.
        return $this->templateDir . $template;
    }
}