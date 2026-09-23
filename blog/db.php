<?php
/**
 * Database connection layer using PDO with prepared statements.
 * Production ready for Hostinger MySQL/MariaDB with automatic local dev fallback.
 */

require_once __DIR__ . '/config.php';

function get_db(): PDO {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $dbType = 'mysql';
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    // Try MySQL if DB_PASS is provided or DB_NAME is set and not default
    if (!empty(DB_PASS) || (defined('FORCE_MYSQL') && FORCE_MYSQL)) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            init_database_schema($pdo, 'mysql');
            run_scheduled_publisher($pdo);
            return $pdo;
        } catch (PDOException $e) {
            // Log error silently, fallback to SQLite for local development preview
            error_log("MySQL connection failed: " . $e->getMessage() . " - Falling back to SQLite.");
        }
    }

    // SQLite fallback for local development preview
    $dataDir = dirname(SQLITE_PATH);
    if (!is_dir($dataDir)) {
        @mkdir($dataDir, 0755, true);
    }
    
    $dsn = "sqlite:" . SQLITE_PATH;
    $pdo = new PDO($dsn, null, null, $options);
    $pdo->exec("PRAGMA foreign_keys = ON;");
    init_database_schema($pdo, 'sqlite');
    run_scheduled_publisher($pdo);
    return $pdo;
}

/**
 * Initialize database schema if tables do not exist
 */
function init_database_schema(PDO $pdo, string $driver): void {
    if ($driver === 'sqlite') {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT NOT NULL UNIQUE,
                email TEXT NOT NULL UNIQUE,
                password_hash TEXT NOT NULL,
                display_name TEXT NOT NULL DEFAULT 'Dhanaji Prajapati',
                role TEXT NOT NULL DEFAULT 'admin',
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS categories (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                slug TEXT NOT NULL UNIQUE,
                description TEXT,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS tags (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                slug TEXT NOT NULL UNIQUE,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS posts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT NOT NULL,
                slug TEXT NOT NULL UNIQUE,
                excerpt TEXT,
                content TEXT,
                featured_image TEXT,
                featured_image_alt TEXT,
                author_id INTEGER NOT NULL DEFAULT 1,
                category_id INTEGER,
                status TEXT NOT NULL DEFAULT 'draft',
                publish_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                seo_title TEXT,
                meta_description TEXT,
                canonical_url TEXT,
                robots TEXT NOT NULL DEFAULT 'index, follow',
                og_title TEXT,
                og_description TEXT,
                og_image TEXT,
                is_featured INTEGER NOT NULL DEFAULT 0,
                show_in_archive INTEGER NOT NULL DEFAULT 1,
                allow_comments INTEGER NOT NULL DEFAULT 0,
                reading_time INTEGER NOT NULL DEFAULT 5,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
            );

            CREATE TABLE IF NOT EXISTS post_tags (
                post_id INTEGER NOT NULL,
                tag_id INTEGER NOT NULL,
                PRIMARY KEY (post_id, tag_id),
                FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
                FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS media (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                filename TEXT NOT NULL,
                filepath TEXT NOT NULL,
                filetype TEXT NOT NULL,
                filesize INTEGER NOT NULL DEFAULT 0,
                alt_text TEXT,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS settings (
                key_name TEXT PRIMARY KEY,
                key_value TEXT
            );
        ");
    }

    // Seed default admin user if not present
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $hash = password_hash(DEFAULT_ADMIN_PASS, PASSWORD_DEFAULT);
        $insert = $pdo->prepare("INSERT INTO users (username, email, password_hash, display_name, role) VALUES (?, ?, ?, ?, 'admin')");
        $insert->execute([DEFAULT_ADMIN_USER, DEFAULT_ADMIN_EMAIL, $hash, 'Dhanaji Prajapati']);
    }

    // Seed categories if empty
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $categories = [
            ['Technical SEO', 'technical-seo', 'In-depth architecture, crawlability, indexation, and core web vitals engineering.'],
            ['AI Search & LLMs', 'ai-search-llms', 'Generative engine optimization, AI Overviews, entity mapping, and citation graph visibility.'],
            ['Analytics & Attribution', 'analytics-attribution', 'Executive dashboards, Google Search Console big data, GA4 tracking, and Looker Studio pipelines.']
        ];
        $catStmt = $pdo->prepare("INSERT INTO categories (name, slug, description) VALUES (?, ?, ?)");
        foreach ($categories as $c) {
            $catStmt->execute($c);
        }
    }

    // Seed tags if empty
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tags");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $tags = [
            ['Crawlability', 'crawlability'],
            ['Indexing', 'indexing'],
            ['Looker Studio', 'looker-studio'],
            ['AI Overviews', 'ai-overviews'],
            ['Core Web Vitals', 'core-web-vitals'],
            ['Technical Audit', 'technical-audit']
        ];
        $tagStmt = $pdo->prepare("INSERT INTO tags (name, slug) VALUES (?, ?)");
        foreach ($tags as $t) {
            $tagStmt->execute($t);
        }
    }

    // Seed initial posts if empty
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM posts");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        seed_initial_posts($pdo);
    }
}

/**
 * Server-side automatic scheduled publisher
 * Publishes scheduled posts whose publish_date has passed
 */
function run_scheduled_publisher(PDO $pdo): int {
    try {
        $now = date('Y-m-d H:i:s');
        $stmt = $pdo->prepare("UPDATE posts SET status = 'published', updated_at = ? WHERE status = 'scheduled' AND publish_date <= ?");
        $stmt->execute([$now, $now]);
        return $stmt->rowCount();
    } catch (Exception $e) {
        error_log("Scheduled publisher error: " . $e->getMessage());
        return 0;
    }
}

/**
 * Seed approved baseline posts
 */
function seed_initial_posts(PDO $pdo): void {
    $now = date('Y-m-d H:i:s');

    $posts = [
        [
            'title' => 'How to Fix Crawlability and Indexing Issues: The Comprehensive Diagnostic Guide',
            'slug' => 'how-to-fix-crawlability-and-indexing-issues',
            'excerpt' => 'A step-by-step technical framework to resolve "Discovered – currently not indexed", canonical mismatches, rogue robots.txt directives, and server response code bottlenecks in Google Search Console.',
            'content' => '<h2>1. The Difference Between Crawlability and Indexability</h2>
<p>A fundamental mistake junior marketers make is conflating <em>crawling</em> with <em>indexing</em>:</p>
<ul>
  <li><strong>Crawlability:</strong> The ability of Googlebot to fetch a URL, read its server HTTP response headers, follow its internal links, and parse its DOM elements without encountering server timeouts, rate limits, or infinite parameter loops.</li>
  <li><strong>Indexability:</strong> Google\'s qualitative and technical decision to store the rendered document inside its searchable database and serve it in search results for relevant user queries.</li>
</ul>
<p>A page can be crawlable but not indexable (e.g., carrying a <code>noindex</code> meta tag). Conversely, a page can be blocked from crawling via <code>robots.txt</code> while still appearing indexed as an orphaned URL citation if strong external backlinks point to it.</p>

<h2>2. Isolating Server Log Directives & Bottlenecks</h2>
<p>Never rely solely on Google Search Console sample data when diagnosing enterprise crawl budgets. Run real server access log analysis parsing user-agent strings:</p>
<pre><code>grep -i "Googlebot" /var/log/nginx/access.log | awk \'{print $9}\' | sort | uniq -c | sort -nr</code></pre>
<p>Evaluate your HTTP status code distribution. If 5xx errors or 429 rate limit exceptions exceed 0.5% of total Googlebot requests, your hosting infrastructure is actively throttling search bot discovery.</p>

<h2>3. Resolving "Discovered – currently not indexed"</h2>
<p>When Google flags URLs as "Discovered – currently not indexed", it signals that Googlebot discovered the URL (via sitemaps or internal links) but calculated that crawling it was not worthwhile based on current crawl budget and domain quality scores.</p>
<p>To fix this:</p>
<ol>
  <li>Audit internal linking depth: ensure all priority money pages are reachable within 3 clicks of the homepage.</li>
  <li>Prune soft 404s and thin parameter variations.</li>
  <li>Inject high-authority contextual in-content anchors rather than burying URLs in generic footer navigation.</li>
</ol>',
            'category_id' => 1,
            'reading_time' => 12,
            'is_featured' => 1,
            'seo_title' => 'Fix Crawlability & Indexing Issues: Technical Guide | Dhanaji Prajapati',
            'meta_description' => 'Comprehensive technical guide on diagnosing and fixing Google Search Console crawl errors, canonical loops, and indexation bottlenecks.',
            'canonical_url' => 'https://dhanajiprajapati.com/blog/how-to-fix-crawlability-and-indexing-issues/',
            'robots' => 'index, follow',
            'og_title' => 'How to Fix Crawlability and Indexing Issues: The Comprehensive Diagnostic Guide',
            'og_description' => 'A step-by-step technical framework to resolve indexation bottlenecks in Google Search Console.',
            'featured_image' => '',
            'featured_image_alt' => 'Google Search Console Crawlability Diagram'
        ],
        [
            'title' => 'Traditional SEO vs AI Search Visibility: What Actually Drives LLM Citations',
            'slug' => 'traditional-seo-vs-ai-search-visibility',
            'excerpt' => 'Comparing blue link ranking algorithms with generative AI synthesis across Google AI Overviews, Perplexity, and ChatGPT Search. Learn how entity structure and citation nodes dictate AI inclusion.',
            'content' => '<h2>1. From Ten Blue Links to Generative Synthesis</h2>
<p>Traditional SEO was optimized for informational query retrieval: ranking an individual document for target keyword density, PageRank link graphs, and user engagement signals. Generative AI Search engines (Google AI Overviews, Perplexity, OpenAI SearchGPT) operate on distinct retrieval-augmented generation (RAG) paradigms.</p>

<h2>2. Entity Extraction & Information Gain</h2>
<p>LLMs evaluate content through information gain scores. Re-summarizing existing top-10 search results yields zero unique extraction value. High-citation documents exhibit:</p>
<ul>
  <li>Proprietary dataset statistics and primary diagnostic experiments.</li>
  <li>Clear semantic entity definitions encapsulated in schema graph markups (<code>SameAs</code>, <code>ItemReviewed</code>, <code>Author</code>).</li>
  <li>Direct, factual syntactic statements that minimize conversational fluff.</li>
</ul>

<h2>3. Structuring Content for Machine Extraction</h2>
<p>Format comparison matrices, step-by-step numbered procedures, and concise summary callout blocks. When an AI crawler parses the DOM, tabular and definition formats are ingested with 4x higher probability into citation vector indices.</p>',
            'category_id' => 2,
            'reading_time' => 10,
            'is_featured' => 0,
            'seo_title' => 'Traditional SEO vs AI Search Visibility Guide | Dhanaji Prajapati',
            'meta_description' => 'How to optimize for Google AI Overviews, Perplexity, and LLM search engines using entity graphs and RAG optimization.',
            'canonical_url' => 'https://dhanajiprajapati.com/blog/traditional-seo-vs-ai-search-visibility/',
            'robots' => 'index, follow',
            'og_title' => 'Traditional SEO vs AI Search Visibility: What Drives LLM Citations',
            'og_description' => 'Comparing algorithmic blue link rankings with generative AI synthesis.',
            'featured_image' => '',
            'featured_image_alt' => 'AI Search Visibility Architecture'
        ],
        [
            'title' => 'Building Executive Looker Studio SEO Dashboards: From GSC Data to Revenue Attribution',
            'slug' => 'looker-studio-seo-reporting-guide',
            'excerpt' => 'How to architect automated, client-facing SEO dashboards in Looker Studio connecting Google Search Console and GA4 with zero manual data entry. Complete with regex filter patterns and calculated fields.',
            'content' => '<h2>1. The Flaw of Manual PDF Reporting</h2>
<p>Manual agency reporting reports obsolete snapshots. Executive stakeholders want real-time visibility into keyword portfolio shifts, branded vs non-branded organic query trends, and conversion attribution tied directly to Google Analytics 4 key events.</p>

<h2>2. Connecting GSC Big Data Without Row Throttling</h2>
<p>The standard Looker Studio GSC connector samples data at 5,000 rows per day. For large domains, this obscures long-tail query performance. By piping GSC Bulk Data Exports into Google BigQuery, you preserve 100% of impressions and clicks.</p>

<h2>3. Essential Calculated Fields & Regex Filters</h2>
<p>Isolate branded versus non-branded traffic instantly using calculated dimension fields:</p>
<pre><code>CASE
  WHEN REGEXP_MATCH(Query, \'(?i).*(dhanaji|prajapati).*\' ) THEN \'Branded\'
  ELSE \'Non-Branded\'
END</code></pre>
<p>This single calculated field provides executives with immediate proof of whether growth stems from brand awareness campaigns or genuine non-branded technical authority improvements.</p>',
            'category_id' => 3,
            'reading_time' => 14,
            'is_featured' => 0,
            'seo_title' => 'Executive Looker Studio SEO Dashboard Guide | Dhanaji Prajapati',
            'meta_description' => 'Architect automated client-facing SEO dashboards in Looker Studio connecting Search Console and GA4 without manual entry.',
            'canonical_url' => 'https://dhanajiprajapati.com/blog/looker-studio-seo-reporting-guide/',
            'robots' => 'index, follow',
            'og_title' => 'Building Executive Looker Studio SEO Dashboards',
            'og_description' => 'How to architect automated SEO dashboards in Looker Studio.',
            'featured_image' => '',
            'featured_image_alt' => 'Looker Studio Dashboard Architecture'
        ]
    ];

    $sql = "INSERT INTO posts (
        title, slug, excerpt, content, featured_image, featured_image_alt,
        author_id, category_id, status, publish_date, seo_title, meta_description,
        canonical_url, robots, og_title, og_description, is_featured, show_in_archive,
        allow_comments, reading_time, created_at, updated_at
    ) VALUES (
        ?, ?, ?, ?, ?, ?, 1, ?, 'published', ?, ?, ?, ?, ?, ?, ?, ?, 1, 0, ?, ?, ?
    )";

    $stmt = $pdo->prepare($sql);
    foreach ($posts as $p) {
        $stmt->execute([
            $p['title'],
            $p['slug'],
            $p['excerpt'],
            $p['content'],
            $p['featured_image'],
            $p['featured_image_alt'],
            $p['category_id'],
            $now,
            $p['seo_title'],
            $p['meta_description'],
            $p['canonical_url'],
            $p['robots'],
            $p['og_title'],
            $p['og_description'],
            $p['is_featured'],
            $p['reading_time'],
            $now,
            $now
        ]);
    }
}
