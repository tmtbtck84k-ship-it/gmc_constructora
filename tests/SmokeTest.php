<?php
/**
 * Smoke test E2E — ejecuta una secuencia mínima que toca todos los módulos críticos
 * para verificar que el sistema "respira" tras un deploy.
 *
 * Uso:
 *   php tests/SmokeTest.php
 *
 * No depende de PHPUnit (autocontenido) para que sea ejecutable en el VPS sin
 * dev-dependencies. Usa cURL contra la app desplegada.
 *
 * Variables de entorno opcionales:
 *   GMC_BASE_URL  (default http://gmc.local)
 *   GMC_RUT       (default 11111111-1)
 *   GMC_PASSWORD  (default GMC.2026!)
 */

class SmokeTest
{
    private $baseUrl;
    private $cookieJar;
    private $rut;
    private $password;
    private $passed = 0;
    private $failed = 0;
    private $lastBody = '';
    private $lastCode = 0;
    private $lastHeaders = '';

    public function __construct()
    {
        $this->baseUrl  = rtrim(getenv('GMC_BASE_URL') ?: 'http://gmc.local', '/');
        $this->rut      = getenv('GMC_RUT')      ?: '11111111-1';
        $this->password = getenv('GMC_PASSWORD') ?: 'GMC.2026!';
        $this->cookieJar = sys_get_temp_dir() . '/gmc-smoke-' . uniqid() . '.txt';
    }

    public function run(): int
    {
        echo "=== GMC ERP Smoke Test ===\n";
        echo "Base URL: {$this->baseUrl}\n\n";

        // 1. Login page accesible
        $this->test('GET /login responde 200', function () {
            $this->get('/login');
            return $this->lastCode === 200 && stripos($this->lastBody, 'rut') !== false;
        });

        // 2. Login fallido devuelve a /login
        $this->test('Login con clave errónea NO autentica', function () {
            $this->postLogin($this->rut, 'wrong-password');
            $body = $this->get('/dashboard');
            return strpos($this->lastBody, 'Iniciar sesión') !== false ||
                   strpos($this->lastBody, 'login') !== false ||
                   $this->lastCode >= 300;
        });

        // 3. Login exitoso
        $this->test('Login válido autentica', function () {
            $this->postLogin($this->rut, $this->password);
            return $this->lastCode === 200 || $this->lastCode === 302;
        });

        // 4. Dashboard accesible (o redirección a /password/change si force=1)
        $this->test('GET /dashboard accesible', function () {
            $this->get('/dashboard');
            // Cualquiera de estos contenidos confirma que la sesión es válida y se renderizó algo:
            $marcadores = ['Bienvenido', 'ERP GMC', 'Cambiar contraseña', 'Dashboard'];
            if ($this->lastCode !== 200) return false;
            foreach ($marcadores as $m) {
                if (stripos($this->lastBody, $m) !== false) return true;
            }
            return false;
        });

        // 5. Endpoints clave responden 200
        foreach ([
            '/maestros/clientes',
            '/maestros/proveedores',
            '/maestros/proyectos',
            '/maestros/centros-costo',
            '/maestros/tipos-gasto',
            '/maestros/tipos-cambio',
            '/finanzas/sdp',
            '/compras/compras',
            '/compras/rindes',
            '/obras/bitacora',
            '/obras/cierre',
            '/obras/presupuesto',
            '/reportes/pagos',
            '/reportes/gastos',
            '/admin/usuarios',
            '/admin/roles',
            '/admin/auditoria',
        ] as $path) {
            $this->test("GET {$path} responde 200", function () use ($path) {
                $this->get($path);
                return $this->lastCode === 200;
            });
        }

        // 6. Logout
        $this->test('Logout invalida sesión', function () {
            $this->get('/logout');
            $this->get('/dashboard');  // tras logout debe redirigir/bloquear
            return $this->lastCode !== 200 || stripos($this->lastBody, 'login') !== false;
        });

        // Limpieza
        @unlink($this->cookieJar);

        // Resumen
        $total = $this->passed + $this->failed;
        echo "\n=== Resultado ===\n";
        echo "Pasados: {$this->passed}/{$total}\n";
        echo "Fallidos: {$this->failed}/{$total}\n";

        return $this->failed === 0 ? 0 : 1;
    }

    private function test(string $nombre, callable $fn): void
    {
        try {
            $ok = (bool)$fn();
        } catch (\Throwable $e) {
            $ok = false;
            echo "  ERR: " . $e->getMessage() . "\n";
        }
        if ($ok) {
            $this->passed++;
            echo "[OK] {$nombre}\n";
        } else {
            $this->failed++;
            echo "[FAIL] {$nombre} (HTTP {$this->lastCode})\n";
        }
    }

    private function get(string $path): string
    {
        $ch = curl_init($this->baseUrl . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_COOKIEJAR      => $this->cookieJar,
            CURLOPT_COOKIEFILE     => $this->cookieJar,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $this->lastBody = (string)curl_exec($ch);
        $this->lastCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $this->lastBody;
    }

    private function postLogin(string $rut, string $password): void
    {
        // Obtener CSRF
        $this->get('/login');
        $token = '';
        if (preg_match('/name="csrf_gmc"\s+value="([^"]+)"/', $this->lastBody, $m)) {
            $token = $m[1];
        }
        $ch = curl_init($this->baseUrl . '/login/submit');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query([
                'csrf_gmc' => $token, 'rut' => $rut, 'password' => $password,
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_COOKIEJAR      => $this->cookieJar,
            CURLOPT_COOKIEFILE     => $this->cookieJar,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $this->lastBody = (string)curl_exec($ch);
        $this->lastCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    }
}

if (PHP_SAPI === 'cli') {
    $t = new SmokeTest();
    exit($t->run());
}
