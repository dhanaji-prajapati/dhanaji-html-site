/**
 * Dhanaji Prajapati — Independent Technical SEO Consultant
 * Pure Vanilla JavaScript (ES6+)
 * No external framework dependencies. Optimized for Hostinger Static Hosting.
 */

function initApp() {
  initMobileNavigation();
  initSearchTerminal();
  initFaqAccordions();
  initContactForm();
  initLogoSlider();
  initStickyFloatingBanner();
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initApp);
} else {
  initApp();
}

/* ----------------------------------------------------
   1. MOBILE NAVIGATION (Accessible Drawer)
   ---------------------------------------------------- */
function initMobileNavigation() {
  const toggleBtn = document.getElementById('mobile-menu-btn') || document.getElementById('mobile-menu-toggle');
  const closeBtn = document.getElementById('mobile-menu-close');
  const drawer = document.getElementById('mobile-menu-drawer') || document.getElementById('mobile-drawer');
  const backdrop = document.getElementById('mobile-menu-backdrop') || document.getElementById('mobile-backdrop');

  if (!toggleBtn || !drawer) return;

  function isMenuOpen() {
    return !drawer.classList.contains('hidden');
  }

  function openMenu() {
    drawer.classList.remove('hidden');
    drawer.setAttribute('aria-hidden', 'false');
    toggleBtn.setAttribute('aria-expanded', 'true');
    document.body.classList.add('overflow-hidden');
  }

  function closeMenu() {
    drawer.classList.add('hidden');
    drawer.setAttribute('aria-hidden', 'true');
    toggleBtn.setAttribute('aria-expanded', 'false');
    document.body.classList.remove('overflow-hidden');
  }

  // Toggle button tap/click
  toggleBtn.addEventListener('click', (e) => {
    e.preventDefault();
    e.stopPropagation();
    if (isMenuOpen()) {
      closeMenu();
    } else {
      openMenu();
    }
  });

  // Close button tap/click
  if (closeBtn) {
    closeBtn.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      closeMenu();
    });
  }

  // Backdrop tap/click
  if (backdrop) {
    backdrop.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      closeMenu();
    });
  }

  // Close on Escape key
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && isMenuOpen()) {
      closeMenu();
    }
  });

  // Event delegation for links inside the drawer:
  // Ensures all current and future links in the drawer close the menu without blocking navigation
  drawer.addEventListener('click', (e) => {
    const link = e.target.closest('a');
    if (!link) return;

    // For tel: and mailto: or internal/external links, ensure menu closes cleanly
    closeMenu();
  });
}

/* ----------------------------------------------------
   STICKY FLOATING BANNER
   ---------------------------------------------------- */
function initStickyFloatingBanner() {
  const banner = document.getElementById('sticky-floating-banner');
  const closeBtn = document.getElementById('close-sticky-banner');
  if (!banner) return;

  if (sessionStorage.getItem('sticky_banner_dismissed') === '1') {
    banner.classList.add('hidden');
    return;
  }

  if (closeBtn) {
    closeBtn.addEventListener('click', (e) => {
      e.preventDefault();
      banner.classList.add('hidden');
      sessionStorage.setItem('sticky_banner_dismissed', '1');
    });
  }
}

/* ----------------------------------------------------
   2. SEARCH TERMINAL (Automatic Q&A Typewriter)
   ---------------------------------------------------- */
function initSearchTerminal() {
  const terminal = document.querySelector('.search-terminal-container');
  if (!terminal) return;

  // Standardize Search Terminal header branding sitewide
  const headerDotsContainer = terminal.querySelector('.flex.items-center.space-x-2');
  if (headerDotsContainer) {
    headerDotsContainer.innerHTML = `
      <span class="w-2.5 h-2.5 rounded-full bg-red-500 inline-block" aria-hidden="true"></span>
      <span class="w-2.5 h-2.5 rounded-full bg-yellow-400 inline-block" aria-hidden="true"></span>
      <span class="w-2.5 h-2.5 rounded-full bg-green-500 inline-block" aria-hidden="true"></span>
      <span class="text-slate-300 font-mono ml-1.5 text-[11px] font-semibold tracking-wider uppercase">Search Terminal</span>
    `;
  }

  const pageId = terminal.getAttribute('data-terminal-page') || 'home';
  const queryEl = terminal.querySelector('.terminal-query');
  const resultEl = terminal.querySelector('.terminal-result');
  const tagEl = terminal.querySelector('.terminal-tag');

  // Pre-configured Q&A lists strictly unique per page intent
  const terminalData = {
    home: [
      {
        tag: "DIRECT ACCESS",
        q: "Who actually performs the technical SEO work on my website?",
        a: "100% direct execution by Dhanaji Prajapati. No account-manager telephone games, no junior handoffs, and no outsourced contractors."
      },
      {
        tag: "AI VISIBILITY",
        q: "How does my brand earn recommendations in ChatGPT and Perplexity?",
        a: "By building verified entity authority, clear Schema.org semantic graphs, and authoritative third-party consensus citations across the web."
      },
      {
        tag: "INDEXATION",
        q: "Why does Google Search Console show 'Discovered - currently not indexed'?",
        a: "Internal link starvation, crawl budget waste on low-utility facets, or thin content thresholds. We fix architecture to index core URLs."
      },
      {
        tag: "EXPERIENCE",
        q: "How long has Dhanaji been optimizing technical search architecture?",
        a: "15+ years of hands-on SEO engineering, navigating every major Google Core, Helpful Content, and indexing shift since 2011."
      },
      {
        tag: "ACCOUNTABILITY",
        q: "What makes working with an independent specialist different from an agency?",
        a: "Your retainer pays entirely for senior technical diagnostics and code execution rather than agency sales commissions and overhead."
      }
    ],

    services: [
      {
        tag: "CRAWL EFFICIENCY",
        q: "What causes crawl budget exhaustion on multi-thousand page sites?",
        a: "Unfiltered faceted navigation, parameter loops, and orphan pages. We implement strict robots directives and clean canonical hierarchies."
      },
      {
        tag: "REVENUE ALIGNMENT",
        q: "How do you connect technical SEO fixes directly to commercial pipeline?",
        a: "By prioritizing pages with verified commercial intent, optimizing conversion paths, and building Looker Studio revenue attribution models."
      },
      {
        tag: "AI & GOOGLE",
        q: "How do you optimize for both traditional Google and modern AI search?",
        a: "Traditional SEO secures foundational crawlability and high rankings; semantic entity markup and structured data feed AI answer engines."
      },
      {
        tag: "INTERNAL LINKING",
        q: "How do you calculate and rebalance internal PageRank distribution?",
        a: "Custom crawl-depth audits ensure high-margin commercial landing pages sit within 3 clicks of the root domain with contextual anchor text."
      },
      {
        tag: "ARCHITECTURE",
        q: "Can you fix rendering issues on modern JavaScript frameworks?",
        a: "Yes. We diagnose hydration lag, client-side rendering drops, and dynamic content gaps across Next.js, Nuxt, React, and Angular stacks."
      }
    ],

    training: [
      {
        tag: "CURRICULUM",
        q: "Do you teach actionable technical SEO or just basic on-page theory?",
        a: "Pure practical execution: live server headers, log file analysis, Screaming Frog audits, JavaScript debugging, and schema architecture."
      },
      {
        tag: "WORKFLOW",
        q: "Will we audit my actual company website during the training sessions?",
        a: "Yes. Every practical exercise is conducted directly on your live domain, fixing real-world indexing and crawl issues in real time."
      },
      {
        tag: "CAREER GROWTH",
        q: "Will this training prepare me for Senior In-House or Agency roles?",
        a: "You master client-ready audits, C-level executive reporting, algorithm diagnostics, and cross-functional engineering communication."
      },
      {
        tag: "TOOL MASTERY",
        q: "Which professional tools are covered during the mentorship?",
        a: "Google Search Console, Google Tag Manager, GA4, Screaming Frog, Looker Studio, BigQuery, and AI-assisted workflow tools."
      },
      {
        tag: "PRACTICE",
        q: "How are complex technical concepts made simple for practitioners?",
        a: "Through interactive screen shares, real-world case breakdowns, step-by-step engineering tickets, and ongoing mentorship feedback."
      }
    ],

    whitelabel: [
      {
        tag: "AGENCY DELIVERY",
        q: "Can you deliver white-label audits under our agency's branding?",
        a: "Yes. All audit documents, keyword blueprints, and Looker Studio reports are delivered fully unbranded or customized with your agency identity."
      },
      {
        tag: "CLIENT-FACING",
        q: "Can Dhanaji join client strategy calls or work strictly behind the scenes?",
        a: "Both. I can act as your senior in-house SEO technical director on calls or operate 100% invisible behind your account management team."
      },
      {
        tag: "CAPACITY",
        q: "How do you ensure rapid turnaround and deep focus across partner agencies?",
        a: "I maintain a focused agency roster with direct senior involvement on every account, eliminating junior handoffs and bureaucratic drag."
      },
      {
        tag: "ECONOMICS",
        q: "How does partnering with Dhanaji compare to hiring a full-time in-house lead?",
        a: "Zero payroll taxes, zero software subscription overhead, and zero recruitment fees. You access 15+ years of senior expertise on demand."
      },
      {
        tag: "CLIENT OWNERSHIP",
        q: "Does our agency retain 100% ownership of the client relationship?",
        a: "Always. Protected by a strict non-disclosure and non-solicitation agreement. Your clients remain exclusively your agency relationships."
      }
    ],

    pricing: [
      {
        tag: "ENGAGEMENT",
        q: "Why don't you offer generic low-cost SEO packages?",
        a: "Every domain has distinct technical debt, backlink health, competition intensity, and business targets. Generic packages produce zero revenue."
      },
      {
        tag: "FIRST MONTH",
        q: "How does the 'Claim Your First Month Free' offer work?",
        a: "For qualified businesses on 6-month growth partnerships, month 1 is focused on deep diagnostic auditing and quick wins with zero advisory fees."
      },
      {
        tag: "NO LOCK-IN",
        q: "Are your consulting agreements bound to restrictive lock-in contracts?",
        a: "No lock-in contracts. Monthly retainers operate on mutual momentum and clear milestones with 30-day transparent cancellation notice."
      },
      {
        tag: "DELIVERABLES",
        q: "What concrete deliverables are provided each month?",
        a: "Prioritized engineering tickets, on-page optimization copy, technical crawl fixes, live Looker Studio dashboard, and bi-weekly strategy calls."
      },
      {
        tag: "DIRECT VALUE",
        q: "Who answers my questions when technical roadblocks occur?",
        a: "You deal directly with me, Dhanaji Prajapati. No junior account reps, no ticket queues, and no administrative friction."
      }
    ],

    about: [
      {
        tag: "INDEPENDENT PATH",
        q: "Why do you work as an independent consultant rather than running a large agency?",
        a: "Agencies inevitably burden senior talent with sales and HR while delegating execution to juniors. I choose to remain directly on the tools."
      },
      {
        tag: "15+ YEARS",
        q: "What experience informs your technical SEO recommendations?",
        a: "15+ years navigating Google algorithmic updates, large-scale migrations, and enterprise search architecture across international markets."
      },
      {
        tag: "COLLABORATION",
        q: "How do clients collaborate directly with Dhanaji?",
        a: "Direct Slack or WhatsApp channels, bi-weekly Zoom consultations, and shared Google Drive / Notion boards for transparent task tracking."
      },
      {
        tag: "DEVELOPER SUPPORT",
        q: "Will software engineers actually respect and implement your recommendations?",
        a: "Yes. I write production-ready code snippets, exact schema markup, and step-by-step developer tickets directly tailored to your tech stack."
      },
      {
        tag: "ACCOUNTABILITY",
        q: "What is your commitment to client outcomes?",
        a: "Complete personal accountability. If an issue arises, I investigate and resolve it personally with direct access at all times."
      }
    ],

    portfolio: [
      {
        tag: "B2B PIPELINE",
        q: "What was the technical fix for the +214% B2B SaaS demo request increase?",
        a: "Pruned 45,000 waste facet URLs, repaired canonical tag loops, and optimized crawl paths for transactional landing pages."
      },
      {
        tag: "MIGRATION",
        q: "How was zero traffic loss achieved across the 42-location healthcare site?",
        a: "1:1 legacy URL redirect mapping across 8,400 endpoints, pre-launch staging audits, and nested Schema.org medical entity graphs."
      },
      {
        tag: "CORE WEB VITALS",
        q: "How did you reduce e-commerce mobile INP from 420ms failing to 78ms pass?",
        a: "Migrated tracking scripts to server-side GTM, broke up long JavaScript tasks, and prioritized above-the-fold hero rendering."
      },
      {
        tag: "RECOVERY",
        q: "What strategy drove the +180% impression rebound after an algorithm drop?",
        a: "Forensic audit: pruned 1,200 low-utility pages via 410 Gone and consolidated 450 thin guides into 40 authoritative pillar entities."
      },
      {
        tag: "VERIFIABILITY",
        q: "Can prospective clients review live performance proof during discovery?",
        a: "Yes. During our initial video call, I share sanitized Looker Studio performance dashboards and anonymized Search Console datasets."
      }
    ],

    contact: [
      {
        tag: "RESPONSE TIME",
        q: "How quickly will Dhanaji reply to my inquiry?",
        a: "I personally review your website URL within 24 hours and reply directly with initial observations and available consultation slots."
      },
      {
        tag: "SCHEDULING",
        q: "Can I book an immediate strategy call on your calendar?",
        a: "Yes. You can book an immediate 30-minute consultation directly via Calendly at calendly.com/dhanajiprajapati12."
      },
      {
        tag: "CONFIDENTIALITY",
        q: "Do you sign Non-Disclosure Agreements (NDAs) before reviewing our website?",
        a: "Yes. I routinely sign mutual NDAs before reviewing proprietary data, pre-launch products, or staging environments."
      },
      {
        tag: "DIRECT ACCESS",
        q: "Can I contact Dhanaji directly for urgent inquiries?",
        a: "Yes. Reach me directly by phone or WhatsApp on +91 97244 83959 or email chatwith@dhanajiprajapati.com."
      },
      {
        tag: "PREREQUISITES",
        q: "What should I have prepared for our introductory call?",
        a: "Just your website URL, primary commercial objectives, and optionally view-only access to Google Search Console."
      }
    ],

    // 1. Conversion Tracking
    'conversion-tracking': [
      {
        tag: "DATA ACCURACY",
        q: "Why don't our GA4 conversions match actual closed leads in our CRM?",
        a: "Mismatched trigger timing, missing form submission listeners, or ad-blockers. We audit dataLayer events to capture 100% of validated leads."
      },
      {
        tag: "REVENUE PROOF",
        q: "How can we prove which organic landing pages actually generate revenue?",
        a: "By connecting Search Console landing page queries with GA4 purchase/lead events via BigQuery, establishing direct revenue attribution per URL."
      },
      {
        tag: "CROSS-DOMAIN",
        q: "Why are our third-party checkout conversions showing as Direct/None?",
        a: "Broken linker parameters between your primary site and checkout portal. We configure proper cross-domain measurement and referral exclusions."
      },
      {
        tag: "PRIVACY DURABILITY",
        q: "Are client-side tracking pixels losing conversion data to iOS updates?",
        a: "Yes. Safari ITP restricts 3rd-party cookies. Server-side Tag Manager on a first-party subdomain restores durable tracking reliability."
      },
      {
        tag: "PHONE CALLS",
        q: "Can we track high-intent telephone calls generated by our SEO landing pages?",
        a: "Yes. Dynamic Number Insertion (DNI) linked to Google Tag Manager attributes phone calls and call durations directly back to organic search."
      }
    ],

    // 2. Reporting Dashboards
    'reporting-dashboards': [
      {
        tag: "REPORTING TIME",
        q: "Why does our team waste 12+ hours every month manually assembling client reports?",
        a: "Manual copy-pasting across disparate tools creates friction. Automated Looker Studio pipelines pull live GSC, GA4, and ranking data instantly."
      },
      {
        tag: "UNIFIED DATA",
        q: "Can Looker Studio combine organic traffic, keyword rankings, and CRM sales in one view?",
        a: "Yes. We blend Search Console search volume, GA4 session paths, and HubSpot or Salesforce pipeline stages into a unified executive dashboard."
      },
      {
        tag: "PERFORMANCE",
        q: "How do we prevent Looker Studio dashboards from breaking or timing out on large sites?",
        a: "We route large raw datasets through partitioned Google BigQuery tables and caching extracts instead of running live, heavy API queries."
      },
      {
        tag: "WHITE LABEL",
        q: "Do you build white-label reporting templates that agencies can present to clients?",
        a: "Yes. Fully customized with your agency branding, logos, color palette, and bespoke KPI definitions ready for monthly presentation."
      },
      {
        tag: "INTERACTIVITY",
        q: "Can stakeholders filter organic performance by product categories or regions?",
        a: "Yes. Interactive controls allow executives to filter data by URL directory, country, device category, or query intent with one click."
      }
    ],

    // 3. GBP Suspension Recovery
    'gbp-suspension-recovery': [
      {
        tag: "REINSTATEMENT",
        q: "My Google Business Profile was suddenly suspended. Can you guarantee reinstatement?",
        a: "No legitimate specialist guarantees reinstatement because final approval rests with Google. We meticulously prepare compliance evidence to maximize odds."
      },
      {
        tag: "ROOT CAUSE",
        q: "What are the most common compliance violations causing GBP suspensions?",
        a: "Mismatched signage, using co-working spaces or virtual PO boxes as physical storefronts, and unauthorized address or category modifications."
      },
      {
        tag: "DOCUMENTATION",
        q: "What official documentation does Google require during the reinstatement appeal?",
        a: "Official utility bills matching the registered address, Secretary of State corporate filings, tax certificates, and unedited exterior storefront video."
      },
      {
        tag: "APPEAL STRATEGY",
        q: "Should we submit another reinstatement appeal if Google rejected our initial ticket?",
        a: "Never submit duplicate appeals before correcting the underlying violation. We conduct a forensic profile audit, align citations, and file a formal escalation."
      },
      {
        tag: "CLINIC GUIDELINES",
        q: "Can multiple Google Business Profiles exist for different doctors at one clinic?",
        a: "Only if individual practitioners have direct public-facing customer access and distinct departmental signage compliant with Google's guidelines."
      }
    ],

    // 4. Website Migration
    'website-migration': [
      {
        tag: "TRAFFIC PROTECTION",
        q: "How do we prevent devastating organic traffic drops when replatforming our website?",
        a: "Pre-launch 1:1 legacy URL redirect mapping, staging crawl parity checks, robots directive audits, and immediate post-launch 404 response monitoring."
      },
      {
        tag: "REDIRECT MAP",
        q: "What happens to our keyword rankings if URL structures change during migration?",
        a: "Without precise 301 redirects, search engines treat new URLs as blank slates, wiping out accumulated historical backlink equity and organic rankings."
      },
      {
        tag: "STAGING AUDIT",
        q: "How do we verify that our staging site is not accidentally indexed by Googlebot?",
        a: "We enforce server-level HTTP Basic Authentication and strict 'X-Robots-Tag: noindex' response headers on all pre-production staging environments."
      },
      {
        tag: "TIMING",
        q: "When should SEO benchmarking and URL inventory mapping begin before site launch?",
        a: "At least 6 to 8 weeks prior to launch to crawl all legacy URLs, inventory top-converting landing pages, and test redirect configurations in staging."
      },
      {
        tag: "MONITORING",
        q: "How long does post-migration monitoring continue after the DNS switch?",
        a: "We perform hourly live crawl audits during launch day, followed by daily Search Console indexation tracking and server log analysis for 45 to 60 days."
      }
    ],

    // 5. No-Index & Crawlability Fixes
    'no-index-crawlability-fixes': [
      {
        tag: "INDEXATION DROP",
        q: "Why does Search Console report 'Discovered - currently not indexed' on our money pages?",
        a: "Internal link starvation, crawl budget exhaustion on low-value pages, or thin content quality thresholds. We restructure architecture to index core URLs."
      },
      {
        tag: "ROBOTS CONFLICT",
        q: "Can an errant meta robots noindex tag accidentally de-index our entire website?",
        a: "Yes. Staging environment noindex tags pushed to production can wipe out indexation within hours. We run automated header checks to detect and reverse them."
      },
      {
        tag: "SITEMAP INTEGRITY",
        q: "Why is Googlebot ignoring URLs submitted in our XML sitemaps?",
        a: "Sitemaps containing redirect chains, 404s, non-canonical targets, or blocked by robots.txt lose Googlebot trust. We clean sitemaps to include only 200 OK canonicals."
      },
      {
        tag: "CRAWL DEPTH",
        q: "How does deep page crawl depth harm our commercial category visibility?",
        a: "Pages buried more than 4 clicks from the homepage receive minimal crawl frequency and internal PageRank. We compress architecture so core pages sit within 3 clicks."
      },
      {
        tag: "CANONICALS",
        q: "Why does Google index our HTTP or non-www URLs instead of our preferred secure domain?",
        a: "Inconsistent internal linking, missing server 301 redirects, or conflicting canonical headers. We enforce site-wide protocol canonicalization at the server level."
      }
    ],

    // 6. Penalty & Algorithm Recovery
    'penalty-algorithm-recovery': [
      {
        tag: "DROP DIAGNOSIS",
        q: "Organic impressions dropped 50% overnight. Was our site hit with a manual penalty?",
        a: "Most sudden declines stem from broad algorithmic core updates rather than manual actions. We inspect Search Console Manual Actions first, then audit algorithmic intent shifts."
      },
      {
        tag: "CORE VS HCU",
        q: "How do we identify whether a drop was caused by a Core Update or Helpful Content Update?",
        a: "We correlate exact drop dates against Google algorithm release windows, analyzing query-level click losses to distinguish content quality issues from technical regressions."
      },
      {
        tag: "PRUNING STRATEGY",
        q: "Can removing low-quality or outdated pages help our website recover from an algorithm drop?",
        a: "Yes. Pruning unhelpful, zero-traffic pages or consolidating overlapping articles into comprehensive pillar pages concentrates topical authority and eliminates site-wide quality drags."
      },
      {
        tag: "RECOVERY TIMELINE",
        q: "How long does it take to see rankings recover after fixing algorithmic quality issues?",
        a: "Algorithmic adjustments typically require Google's evaluation systems to refresh across subsequent core update cycles, generally taking 2 to 4 months of consistent quality signals."
      },
      {
        tag: "BACKLINK AUDIT",
        q: "Do toxic or spammy backlinks trigger automated Google ranking demotions?",
        a: "Google's SpamBrain algorithm generally ignores spam links automatically. However, heavy manual link penalty cases require selective disavow filing and link profile cleanup."
      }
    ],

    // 7. Core Web Vitals
    'core-web-vitals': [
      {
        tag: "INP OPTIMIZATION",
        q: "Why are our product pages failing the Interaction to Next Paint (INP) metric on mobile?",
        a: "Long JavaScript main-thread tasks, heavy third-party tracking tags, and un-optimized event listeners delaying UI responses. We break up long tasks into yield points."
      },
      {
        tag: "LCP TIEBREAKER",
        q: "Can improving Largest Contentful Paint (LCP) directly improve our search rankings?",
        a: "Yes. Page experience serves as a direct ranking tiebreaker for competitive queries, while fast loading drastically reduces mobile bounce rates and improves conversions."
      },
      {
        tag: "CLS CONTAINMENT",
        q: "What causes Cumulative Layout Shift (CLS) on dynamic e-commerce catalog pages?",
        a: "Images, banners, or web fonts without explicit width/height dimensions or font-display fallbacks pushing content downward as assets load."
      },
      {
        tag: "FIELD VS LAB",
        q: "Why do our lab scores in Lighthouse look good while Google Search Console shows failing CWV?",
        a: "Search Console evaluates real-world 75th-percentile field data from actual Chrome users (CrUX) on varied mobile networks, not synthetic emulated lab runs."
      },
      {
        tag: "SERVER LATENCY",
        q: "How do we speed up server response times (TTFB) on international requests?",
        a: "Implementing edge caching through Cloudflare or Fastly CDN, optimizing database queries, and activating HTTP/2 or HTTP/3 server push protocols."
      }
    ],

    // 8. Free Site Review
    'free-site-review': [
      {
        tag: "DIAGNOSTIC SCOPE",
        q: "What specific technical issues do you investigate in the free initial site review?",
        a: "We review core indexation barriers, robots and sitemap directives, mobile rendering, baseline Core Web Vitals, and primary organic visibility bottlenecks."
      },
      {
        tag: "NO BOILERPLATE",
        q: "Is this free review an automated PDF report exported from generic SEO tools?",
        a: "No automated tool exports. I personally spend 30 to 45 minutes manually inspecting your live site architecture and Search Console signals."
      },
      {
        tag: "PRIORITIZATION",
        q: "How do you prioritize which technical problems our team should fix first?",
        a: "We categorize findings by impact and engineering effort: critical blockers affecting revenue first, followed by structural improvements and quick architectural wins."
      },
      {
        tag: "REQUIREMENTS",
        q: "What do you need from our team to conduct the initial review?",
        a: "Simply your website URL, primary commercial objectives, and optionally view-only access to your Google Search Console property."
      },
      {
        tag: "ACTIONABLE OUTPUT",
        q: "Will we receive actionable next steps or just a list of generic warnings?",
        a: "You receive specific, actionable recommendations outlining the exact code or architectural fixes needed to remove search engine barriers."
      }
    ],

    // 9. One-to-One SEO Training
    'one-to-one': [
      {
        tag: "LIVE AUDIT",
        q: "Can we use my company's live production website for our one-to-one training sessions?",
        a: "Yes. 100% of our 1-on-1 mentorship is conducted on your actual domain, auditing live search issues and implementing real fixes in real time."
      },
      {
        tag: "BESPOKE SPRINT",
        q: "How is the curriculum tailored to my current technical SEO knowledge level?",
        a: "We conduct a baseline skills assessment before our first call and build a bespoke sprint covering only the topics you need—from log files to AI search schemas."
      },
      {
        tag: "DIRECT ACCESS",
        q: "Do I get direct access to Dhanaji for questions between our scheduled sessions?",
        a: "Yes. One-to-one students have direct access via a private Slack or WhatsApp channel to ask questions and review audit findings throughout the program."
      },
      {
        tag: "PRACTICAL SKILLS",
        q: "Will this training prepare me to lead technical SEO initiatives independently?",
        a: "You master diagnosing complex crawl anomalies, writing developer-ready engineering tickets, and communicating ROI to C-level stakeholders."
      },
      {
        tag: "FLEXIBLE PACING",
        q: "What if I need to reschedule or pause a session due to client workload?",
        a: "Sessions can be rescheduled with 24 hours notice. Pacing is flexible to accommodate your live agency or company project deadlines."
      }
    ],

    // 10. Group SEO Training
    'group-training': [
      {
        tag: "TEAM SPRINT",
        q: "How do you structure group training for in-house marketing or engineering teams?",
        a: "Interactive workshop sprints featuring live site teardowns, shared auditing workflows, and collaborative problem-solving tailored to your tech stack."
      },
      {
        tag: "AGENCY UPSKILL",
        q: "Can you train our agency account managers to communicate technical SEO clearly to clients?",
        a: "Yes. We focus on demystifying complex technical concepts into commercial business terms that clients appreciate and approve."
      },
      {
        tag: "SOP & TEMPLATES",
        q: "Do team members receive practical audit templates and standard operating procedures?",
        a: "Every participant receives our tested audit templates, GSC regex libraries, Looker Studio dashboard frameworks, and migration checklists."
      },
      {
        tag: "COHORT SIZE",
        q: "What is the ideal cohort size for maximum engagement and practical learning?",
        a: "We limit team training cohorts to 4–10 participants to ensure every engineer or marketer can ask questions and participate in live exercises."
      },
      {
        tag: "RETENTION",
        q: "Are session recordings and teardown notes available for the team after the cohort?",
        a: "Yes. High-resolution recordings, code snippets, and audit documentation are archived in a private repository for ongoing team onboarding."
      }
    ]
  };

  const qas = terminalData[pageId] || terminalData['home'];
  let currentIndex = 0;
  let isTyping = false;

  function typeText(element, text, speed = 25, callback) {
    element.textContent = '';
    let i = 0;
    const interval = setInterval(() => {
      if (i < text.length) {
        element.textContent += text.charAt(i);
        i++;
      } else {
        clearInterval(interval);
        if (callback) callback();
      }
    }, speed);
  }

  function displayQA(index) {
    if (isTyping) return;
    isTyping = true;

    const item = qas[index];
    if (tagEl) tagEl.textContent = item.tag;

    // Type Question
    typeText(queryEl, item.q, 20, () => {
      // Pause then show Answer
      setTimeout(() => {
        typeText(resultEl, item.a, 12, () => {
          // Pause on complete answer before next question
          setTimeout(() => {
            // Smoothly erase
            eraseQA(() => {
              isTyping = false;
              currentIndex = (currentIndex + 1) % qas.length;
              displayQA(currentIndex);
            });
          }, 4000);
        });
      }, 500);
    });
  }

  function eraseQA(callback) {
    const qLen = queryEl.textContent.length;
    const aLen = resultEl.textContent.length;

    const eraseInterval = setInterval(() => {
      let q = queryEl.textContent;
      let a = resultEl.textContent;
      if (q.length > 0) queryEl.textContent = q.slice(0, -3);
      if (a.length > 0) resultEl.textContent = a.slice(0, -5);

      if (queryEl.textContent.length === 0 && resultEl.textContent.length === 0) {
        clearInterval(eraseInterval);
        setTimeout(callback, 300);
      }
    }, 15);
  }

  // Initial trigger
  displayQA(0);
}

/* ----------------------------------------------------
   3. FAQ ACCORDIONS (Accessible, Semantic)
   ---------------------------------------------------- */
function initFaqAccordions() {
  const triggers = document.querySelectorAll('.faq-trigger');

  triggers.forEach(trigger => {
    trigger.addEventListener('click', () => {
      const item = trigger.closest('.faq-item');
      if (!item) return;

      const isActive = item.classList.contains('active');

      // Close other FAQs in the same container for clean editorial feel
      const parentContainer = item.closest('.faq-container');
      if (parentContainer) {
        const activeSiblings = parentContainer.querySelectorAll('.faq-item.active');
        activeSiblings.forEach(sibling => {
          if (sibling !== item) {
            sibling.classList.remove('active');
            const siblingBtn = sibling.querySelector('.faq-trigger');
            if (siblingBtn) siblingBtn.setAttribute('aria-expanded', 'false');
          }
        });
      }

      if (isActive) {
        item.classList.remove('active');
        trigger.setAttribute('aria-expanded', 'false');
      } else {
        item.classList.add('active');
        trigger.setAttribute('aria-expanded', 'true');
      }
    });
  });
}

/* ----------------------------------------------------
   4. CONTACT FORM HANDLING (Direct Formspree Integration)
   ---------------------------------------------------- */
function initContactForm() {
  const form = document.getElementById('contact-form');
  const statusMsg = document.getElementById('form-status');

  if (!form) return;

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn ? submitBtn.textContent : 'Send Message';

    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.textContent = 'Sending...';
    }

    const formData = new FormData(form);

    try {
      const response = await fetch('https://formspree.io/f/xqpkvwov', {
        method: 'POST',
        body: formData,
        headers: {
          'Accept': 'application/json'
        }
      });

      if (response.ok) {
        if (statusMsg) {
          statusMsg.className = 'p-4 rounded bg-slate-100 border border-slate-300 text-slate-800 text-sm block mb-4';
          statusMsg.textContent = 'Thank you! Your message has been sent directly to Dhanaji Prajapati. I will review your website and reply within 24 hours.';
        }
        form.reset();
      } else {
        const data = await response.json();
        if (statusMsg) {
          statusMsg.className = 'p-4 rounded bg-slate-100 border border-slate-300 text-slate-800 text-sm block mb-4';
          statusMsg.textContent = data.errors ? data.errors.map(err => err.message).join(', ') : 'Something went wrong. Please email directly at chatwith@dhanajiprajapati.com.';
        }
      }
    } catch (err) {
      if (statusMsg) {
        statusMsg.className = 'p-4 rounded bg-slate-100 border border-slate-300 text-slate-800 text-sm block mb-4';
        statusMsg.textContent = 'Network error. Please email directly at chatwith@dhanajiprajapati.com or call +91 97244 83959.';
      }
    } finally {
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
      }
    }
  });
}

/* ----------------------------------------------------
   5. CLIENT LOGO SLIDER (Continuous Marquee & Controls)
   ---------------------------------------------------- */
function initLogoSlider() {
  const tracks = document.querySelectorAll('.logo-slider-track');
  if (!tracks.length) return;

  tracks.forEach(track => {
    // Pause animation on pointer interaction or keyboard focus
    track.addEventListener('mouseenter', () => track.classList.add('paused'));
    track.addEventListener('mouseleave', () => track.classList.remove('paused'));
    track.addEventListener('focusin', () => track.classList.add('paused'));
    track.addEventListener('focusout', () => track.classList.remove('paused'));

    // Touch support
    const wrapper = track.closest('.logo-slider-wrapper');
    if (wrapper) {
      wrapper.addEventListener('touchstart', () => {
        track.classList.add('paused');
      }, { passive: true });

      wrapper.addEventListener('touchend', () => {
        setTimeout(() => {
          track.classList.remove('paused');
        }, 1200);
      }, { passive: true });
    }
  });

  // Prev / Next arrow controls
  const prevBtns = document.querySelectorAll('.logo-slider-prev-btn');
  const nextBtns = document.querySelectorAll('.logo-slider-next-btn');

  prevBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      const section = btn.closest('section');
      const track = section ? section.querySelector('.logo-slider-track') : document.querySelector('.logo-slider-track');
      if (!track) return;
      
      track.classList.add('paused');
      const currentTransform = new WebKitCSSMatrix(window.getComputedStyle(track).transform);
      const newX = currentTransform.m41 + 220;
      track.style.transition = 'transform 0.3s ease';
      track.style.transform = `translateX(${Math.min(0, newX)}px)`;
      
      setTimeout(() => {
        track.style.transition = '';
        track.style.transform = '';
        track.classList.remove('paused');
      }, 1500);
    });
  });

  nextBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      const section = btn.closest('section');
      const track = section ? section.querySelector('.logo-slider-track') : document.querySelector('.logo-slider-track');
      if (!track) return;
      
      track.classList.add('paused');
      const currentTransform = new WebKitCSSMatrix(window.getComputedStyle(track).transform);
      const newX = currentTransform.m41 - 220;
      track.style.transition = 'transform 0.3s ease';
      track.style.transform = `translateX(${newX}px)`;
      
      setTimeout(() => {
        track.style.transition = '';
        track.style.transform = '';
        track.classList.remove('paused');
      }, 1500);
    });
  });
}
