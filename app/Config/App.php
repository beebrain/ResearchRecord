<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class App extends BaseConfig
{
    /**
     * --------------------------------------------------------------------------
     * Base Site URL
     * --------------------------------------------------------------------------
     *
     * URL to your CodeIgniter root. Typically, this will be your base URL,
     * WITH a trailing slash:
     *
     * E.g., http://example.com/
     *
     * Auto-detect base URL based on environment
     */
    public string $baseURL = 'http://localhost/researchRecord/';

    public function __construct()
    {
        parent::__construct();

        // Auto-detect base URL based on server environment
        $envBase = env('app.baseURL');
        if (is_string($envBase) && $envBase !== '' && ENVIRONMENT === 'production') {
            $this->baseURL = rtrim($envBase, '/') . '/';

            return;
        }

        if (isset($_SERVER['HTTP_HOST'])) {
            $host = $_SERVER['HTTP_HOST'];

            // Auto-detect protocol (HTTP or HTTPS)
            $isHttps = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
                (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
                (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') ||
                (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

            $protocol = $isHttps ? 'https' : 'http';

            // Detect if running on localhost/development
            $isLocalhost = (strpos($host, 'localhost') !== false) ||
                (strpos($host, '127.0.0.1') !== false) ||
                (strpos($host, '::1') !== false);

            if ($isLocalhost) {
                $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
                // Docker/nginx without rewrite: index.php?/controller/method
                if (stripos($scriptName, 'ResearchRecord/public') !== false) {
                    // Static files: .../public/assets/...  Routes: .../public/index.php?/dashboard
                    $this->baseURL     = $protocol . '://' . $host . '/ResearchRecord/public/';
                    $this->indexPage   = 'index.php?';
                    $this->uriProtocol = 'QUERY_STRING';
                } else {
                    $this->baseURL = $protocol . '://' . $host . '/researchRecord/';
                }
            } else {
                // Production environment - auto-detect path
                $this->baseURL = $this->detectProductionBaseURL($host, $protocol);
            }
        }
    }

    /**
     * Detect production base URL automatically
     */
    private function detectProductionBaseURL(string $host, string $protocol): string
    {
        // Method 1: Use SCRIPT_NAME (most reliable)
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';

        // Remove /index.php from script name to get base path
        $basePath = str_replace('/index.php', '', $scriptName);

        // Method 2: Check SCRIPT_FILENAME to detect /public directory
        $scriptFilename = $_SERVER['SCRIPT_FILENAME'] ?? '';
        $documentRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';

        // If script is in /public directory
        if (!empty($scriptFilename) && !empty($documentRoot)) {
            // Get relative path from document root
            $relativePath = str_replace($documentRoot, '', dirname($scriptFilename));
            $relativePath = str_replace('\\', '/', $relativePath); // Normalize Windows paths

            // If script is in /public, ensure basePath includes it
            if (strpos($relativePath, '/public') !== false || strpos($relativePath, 'public') !== false) {
                // If basePath doesn't already have /public, check if we need to add it
                if (strpos($basePath, '/public') === false && $basePath !== '/') {
                    // Check if the actual file is in public directory
                    if (strpos($scriptFilename, DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR) !== false) {
                        // Only add /public if basePath is root or doesn't match
                        if ($basePath === '/' || empty($basePath)) {
                            $basePath = '/public';
                        }
                    }
                }
            }
        }

        // Normalize basePath
        if (empty($basePath) || $basePath === '/') {
            // Check REQUEST_URI to see if /public is in the path
            $requestUri = $_SERVER['REQUEST_URI'] ?? '';
            if (strpos($requestUri, '/public/') !== false || strpos($requestUri, '/public/index.php') !== false) {
                $basePath = '/public';
            } else {
                $basePath = '/';
            }
        }

        // Ensure basePath starts with /
        if (substr($basePath, 0, 1) !== '/') {
            $basePath = '/' . $basePath;
        }

        // Ensure basePath ends with /
        if (substr($basePath, -1) !== '/') {
            $basePath .= '/';
        }

        $finalURL = $protocol . '://' . $host . $basePath;

        // Debug logging (only in development)
        if (ENVIRONMENT !== 'production') {
            log_message('debug', 'Base URL Detection: ' . json_encode([
                'host' => $host,
                'protocol' => $protocol,
                'script_name' => $_SERVER['SCRIPT_NAME'] ?? 'N/A',
                'script_filename' => $_SERVER['SCRIPT_FILENAME'] ?? 'N/A',
                'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'N/A',
                'request_uri' => $_SERVER['REQUEST_URI'] ?? 'N/A',
                'detected_base_path' => $basePath,
                'final_base_url' => $finalURL
            ]));
        }

        return $finalURL;
    }

    /**
     * Allowed Hostnames in the Site URL other than the hostname in the baseURL.
     * If you want to accept multiple Hostnames, set this.
     *
     * E.g.,
     * When your site URL ($baseURL) is 'http://example.com/', and your site
     * also accepts 'http://media.example.com/' and 'http://accounts.example.com/':
     *     ['media.example.com', 'accounts.example.com']
     *
     * @var list<string>
     */
    public array $allowedHostnames = [];

    /**
     * --------------------------------------------------------------------------
     * Index File
     * --------------------------------------------------------------------------
     *
     * Typically, this will be your `index.php` file, unless you've renamed it to
     * something else. If you have configured your web server to remove this file
     * from your site URIs, set this variable to an empty string.
     */
    public string $indexPage = 'index.php';

    /**
     * --------------------------------------------------------------------------
     * URI PROTOCOL
     * --------------------------------------------------------------------------
     *
     * This item determines which server global should be used to retrieve the
     * URI string. The default setting of 'REQUEST_URI' works for most servers.
     * If your links do not seem to work, try one of the other delicious flavors:
     *
     *  'REQUEST_URI': Uses $_SERVER['REQUEST_URI']
     * 'QUERY_STRING': Uses $_SERVER['QUERY_STRING']
     *    'PATH_INFO': Uses $_SERVER['PATH_INFO']
     *
     * WARNING: If you set this to 'PATH_INFO', URIs will always be URL-decoded!
     */
    public string $uriProtocol = 'REQUEST_URI';

    /*
    |--------------------------------------------------------------------------
    | Allowed URL Characters
    |--------------------------------------------------------------------------
    |
    | This lets you specify which characters are permitted within your URLs.
    | When someone tries to submit a URL with disallowed characters they will
    | get a warning message.
    |
    | As a security measure you are STRONGLY encouraged to restrict URLs to
    | as few characters as possible.
    |
    | By default, only these are allowed: `a-z 0-9~%.:_-`
    |
    | Set an empty string to allow all characters -- but only if you are insane.
    |
    | The configured value is actually a regular expression character group
    | and it will be used as: '/\A[<permittedURIChars>]+\z/iu'
    |
    | DO NOT CHANGE THIS UNLESS YOU FULLY UNDERSTAND THE REPERCUSSIONS!!
    |
    */
    public string $permittedURIChars = 'a-z 0-9~%.:_\-\(\)=';

    /**
     * --------------------------------------------------------------------------
     * Default Locale
     * --------------------------------------------------------------------------
     *
     * The Locale roughly represents the language and location that your visitor
     * is viewing the site from. It affects the language strings and other
     * strings (like currency markers, numbers, etc), that your program
     * should run under for this request.
     */
    public string $defaultLocale = 'en';

    /**
     * --------------------------------------------------------------------------
     * Negotiate Locale
     * --------------------------------------------------------------------------
     *
     * If true, the current Request object will automatically determine the
     * language to use based on the value of the Accept-Language header.
     *
     * If false, no automatic detection will be performed.
     */
    public bool $negotiateLocale = false;

    /**
     * --------------------------------------------------------------------------
     * Supported Locales
     * --------------------------------------------------------------------------
     *
     * If $negotiateLocale is true, this array lists the locales supported
     * by the application in descending order of priority. If no match is
     * found, the first locale will be used.
     *
     * IncomingRequest::setLocale() also uses this list.
     *
     * @var list<string>
     */
    public array $supportedLocales = ['en'];

    /**
     * --------------------------------------------------------------------------
     * Application Timezone
     * --------------------------------------------------------------------------
     *
     * The default timezone that will be used in your application to display
     * dates with the date helper, and can be retrieved through app_timezone()
     *
     * @see https://www.php.net/manual/en/timezones.php for list of timezones
     *      supported by PHP.
     */
    public string $appTimezone = 'UTC';

    /**
     * --------------------------------------------------------------------------
     * Default Character Set
     * --------------------------------------------------------------------------
     *
     * This determines which character set is used by default in various methods
     * that require a character set to be provided.
     *
     * @see http://php.net/htmlspecialchars for a list of supported charsets.
     */
    public string $charset = 'UTF-8';

    /**
     * --------------------------------------------------------------------------
     * Force Global Secure Requests
     * --------------------------------------------------------------------------
     *
     * If true, this will force every request made to this application to be
     * made via a secure connection (HTTPS). If the incoming request is not
     * secure, the user will be redirected to a secure version of the page
     * and the HTTP Strict Transport Security (HSTS) header will be set.
     */
    public bool $forceGlobalSecureRequests = false;

    /**
     * --------------------------------------------------------------------------
     * Reverse Proxy IPs
     * --------------------------------------------------------------------------
     *
     * If your server is behind a reverse proxy, you must whitelist the proxy
     * IP addresses from which CodeIgniter should trust headers such as
     * X-Forwarded-For or Client-IP in order to properly identify
     * the visitor's IP address.
     *
     * You need to set a proxy IP address or IP address with subnets and
     * the HTTP header for the client IP address.
     *
     * Here are some examples:
     *     [
     *         '10.0.1.200'     => 'X-Forwarded-For',
     *         '192.168.5.0/24' => 'X-Real-IP',
     *     ]
     *
     * @var array<string, string>
     */
    public array $proxyIPs = [];

    /**
     * --------------------------------------------------------------------------
     * Content Security Policy
     * --------------------------------------------------------------------------
     *
     * Enables the Response's Content Secure Policy to restrict the sources that
     * can be used for images, scripts, CSS files, audio, video, etc. If enabled,
     * the Response object will populate default values for the policy from the
     * `ContentSecurityPolicy.php` file. Controllers can always add to those
     * restrictions at run time.
     *
     * For a better understanding of CSP, see these documents:
     *
     * @see http://www.html5rocks.com/en/tutorials/security/content-security-policy/
     * @see http://www.w3.org/TR/CSP/
     */
    public bool $CSPEnabled = false;
}
