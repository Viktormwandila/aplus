# Aplus Physics

A working static education site prepared for Cloudflare Workers Static Assets. No database, build service or API keys are needed by the learning app.

## Pages
- `/`: course home
- `/lesson`: seven-part Form 1 laboratory safety lesson and teacher guide
- `/practice`: eight original interactive questions, SVG diagrams, worked answers, MathML and pendulum lab
- `/progress`: device-local progress summary

Run `npm run dev` for a dependency-free local preview.

## Run and deploy
Requires a current Cloudflare-supported Node.js release and a Cloudflare account.

```sh
npm install
npm run check
npx wrangler login
npm run cf:dev
npm run deploy
```

Wrangler prints the deployed workers.dev URL. Choose a unique Worker name in wrangler.jsonc if `aplus-physics` conflicts with an existing project. For Workers Builds, select this folder as root, leave build command empty (or use npm run check), and set deploy command to npm run deploy. Add a custom domain through the Worker's Domains & Routes settings after deployment.

## Storage and offline
Progress is stored in browser localStorage on the deployed origin; it does not sync between devices or transfer from a downloaded HTML file. A service worker caches the course pages after initial online loading. It requires HTTPS or localhost. New deployments should increment the service-worker cache version when asset structure changes.

## Content limits
The safety lesson is an original teaching adaptation with source and verification limits stated on the page. Practice contains 28 original questions in six topic pages mapped to the CDC 2024 Form 1–4 syllabus. Each question has a bespoke labelled SVG, subtopic and competence reference. It is not a full transcription of the 2017 revision booklet or a complete syllabus course. Practical safety/first-aid work requires teacher supervision. No private Drive material or credentials are included.

## Verification
Run npm run check for syntax, IDs, internal links, manifest and deployment-config checks. Browser tests cover answer checks, hints, worked answers and simulation controls. Mobile-device testing and teacher review remain.
