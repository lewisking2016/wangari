---
name: google-indexing
description: >
  Google Search Console indexing workflows, sitemap XML priority mapping,
  and dynamic URL submission for instant indexing.
---

# Google Indexing & Sitemap Strategy

## 1. Sitemap Priority Map
- `/`: `priority 1.0`, `changeFrequency: daily`
- `/pricing`: `priority 0.9`, `changeFrequency: weekly`
- `/features/*`: `priority 0.85`, `changeFrequency: weekly`
- `/about`, `/register`: `priority 0.8`, `changeFrequency: monthly`

## 2. Google Search Indexing API
- Dynamic ping to Google Indexing API on new feature / content publish.
