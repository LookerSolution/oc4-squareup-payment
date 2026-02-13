<?php
namespace Tests\Stub;

class RegistryFactory {
    public static function create(array $configData = []): StubRegistry {
        $registry = new StubRegistry();

        $config = new StubConfig();
        foreach ($configData as $key => $value) {
            $config->set($key, $value);
        }

        $registry->set('config', $config);
        $registry->set('log', new StubLog());
        $registry->set('language', new StubLanguage());
        $registry->set('currency', new StubCurrency());
        $registry->set('db', new StubDb());

        $session = new \stdClass();
        $session->data = [];
        $registry->set('session', $session);

        $registry->set('url', new class {
            public function link(string $route, string $args = ''): string {
                return 'http://localhost/index.php?route=' . $route . '&' . $args;
            }
        });

        return $registry;
    }
}
