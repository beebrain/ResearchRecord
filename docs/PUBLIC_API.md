# Public API – Usage Guide

This document describes the **public API** exposed for external systems. All endpoints are implemented in **`app/Controllers/ApiController.php`** and protected by an API key.

---

## Base URL

- **Local:** `http://localhost/researchRecord/public/index.php` (or your XAMPP base)
- **Production:** `https://your-domain.com/public/index.php` (or as configured)

All public API routes are under: **`/api/public/...`**

---

## Authentication (API Key)

Public API routes use the **`apikey`** filter. You must send a valid API key with every request.

Curriculum detail API is **public** — no authentication required. See [CURRICULUM_DETAIL_API.md](./CURRICULUM_DETAIL_API.md).

| Method | Header / Source |
|--------|------------------|
| **Header** | `X-API-KEY: <your-api-key>` |

- **Default key** (if `API_KEY` is not set in `.env`): **`URU_RESEARCH`**
- **Custom key:** Set `API_KEY=your_secret_key` in `.env` (root or `env` file).

**Filter logic** (`app/Filters/ApiKeyFilter.php`):

- Reads `X-API-KEY` from the request.
- Compares it to `env('API_KEY') ?: 'URU_RESEARCH'`.
- If key is missing or wrong → **401** with JSON: `{ "success": false, "error": "UNAUTHORIZED", "message": "Valid API Key is required in X-API-KEY header" }`.
- If the user is **logged in** (session), the filter allows the request even without a valid API key (for internal use).

**Example (curl):**

```bash
curl -H "X-API-KEY: URU_RESEARCH" "http://localhost/researchRecord/public/index.php/api/public/publications-by-email?email=teacher@example.com"
```

---

## Public API Endpoints (Routes)

Defined in **`app/Config/Routes.php`** under the group `api/public` with filter `apikey`:

| Route | Method | Controller method | Description |
|-------|--------|-------------------|-------------|
| `/api/public/publications-by-email` | GET | `ApiController::apiGetPublicationsByEmail` | Get publications by teacher email |
| `/api/public/search-teachers` | GET | `ApiController::apiSearchTeachers` | Search teachers by name |
| `/api/public/faculty-personnel` | GET | `ApiController::apiGetFacultyPersonnel` | Get personnel (Dean, Chairs, Teachers) for a faculty |

---

## 1. Get publications by email

**Route:** `GET /api/public/publications-by-email`

**Purpose:** Get all publications for a teacher identified by email (primary use for external systems).

**Query parameters:**

| Parameter | Required | Description |
|-----------|----------|-------------|
| `email`  | Yes      | Teacher’s email (valid format). User is resolved by `user.email` or author’s email linked to a user. |

**Success (200):**

```json
{
  "success": true,
  "teacher": {
    "uid": 123,
    "email": "teacher@example.com",
    "name_thai": "...",
    "name_english": "...",
    "faculty": "Faculty name",
    "curriculum": "Curriculum name"
  },
  "publications": [
    {
      "id": 1,
      "title": "...",
      "abstract": "...",
      "publication_type": "journal",
      "source": "...",
      "publication_year": "2024",
      "publication_year_be": 2567,
      "publication_month": "...",
      "volume": "...",
      "pages": "...",
      "doi": "...",
      "isbn": "...",
      "keywords": "...",
      "authors": "...",
      "authors_thai": "...",
      "authors_english": "...",
      "created_at": "..."
    }
  ],
  "total": 1,
  "retrieved_at": "2025-01-27 12:00:00"
}
```

**Errors:**

- **400** – Missing or invalid `email` (`MISSING_EMAIL`, `INVALID_EMAIL`).
- **404** – No user found for that email (`USER_NOT_FOUND`).
- **401** – Invalid or missing API key.

**Example:**

```bash
curl -H "X-API-KEY: URU_RESEARCH" "http://localhost/researchRecord/public/index.php/api/public/publications-by-email?email=teacher@example.com"
```

---

## 2. Search teachers

**Route:** `GET /api/public/search-teachers`

**Purpose:** Search teachers by name (Thai or English).

**Query parameters:**

| Parameter | Required | Description |
|-----------|----------|-------------|
| `q`       | Yes      | Search string (min 2 characters). Matches Thai name, last name, English first/last name. |
| `limit`  | No       | Max results (default 20, max 100). |

**Success (200):**

```json
{
  "success": true,
  "data": [
    {
      "uid": 123,
      "email": "teacher@example.com",
      "name_thai": "...",
      "name_english": "...",
      "faculty": "...",
      "curriculum": "..."
    }
  ],
  "total": 1
}
```

**Errors:**

- **400** – `q` missing or shorter than 2 characters (`SEARCH_TOO_SHORT`).
- **401** – Invalid or missing API key.

**Example:**

```bash
curl -H "X-API-KEY: URU_RESEARCH" "http://localhost/researchRecord/public/index.php/api/public/search-teachers?q=สมชาย&limit=10"
```

---

## 3. Get faculty personnel

**Route:** `GET /api/public/faculty-personnel`

**Purpose:** Get personnel for a faculty: Dean, curriculum chairs, and teachers.

**Query parameters:**

| Parameter      | Required* | Description |
|----------------|-----------|-------------|
| `faculty_id`   | One of   | Faculty ID. |
| `faculty_code` | One of   | Faculty code. |

*At least one of `faculty_id` or `faculty_code` is required.

**Success (200):**

```json
{
  "success": true,
  "faculty": {
    "id": 1,
    "name": "Faculty of ...",
    "code": "FXX"
  },
  "personnel": [
    {
      "uid": 123,
      "email": "...",
      "name_thai": "...",
      "name_english": "...",
      "profile_picture": "...",
      "curriculum": "...",
      "positions": ["คณบดี...", "ประธานหลักสูตร..."],
      "primary_position": "คณบดี..."
    }
  ],
  "total": 5,
  "retrieved_at": "2025-01-27 12:00:00"
}
```

**Errors:**

- **400** – Neither `faculty_id` nor `faculty_code` provided (`MISSING_PARAMETER`).
- **404** – Faculty not found (`FACULTY_NOT_FOUND`).
- **401** – Invalid or missing API key.

**Example:**

```bash
curl -H "X-API-KEY: URU_RESEARCH" "http://localhost/researchRecord/public/index.php/api/public/faculty-personnel?faculty_id=1"
```

---

## CORS

All three public API methods set:

- `Access-Control-Allow-Origin: *`
- `Access-Control-Allow-Methods: GET, OPTIONS`
- `Access-Control-Allow-Headers: Content-Type`

So they can be called from browser-based or other cross-origin clients (subject to your security requirements).

---

## Related (no API key – internal / faculty search)

These use **ApiController** but are **not** under `api/public` and do **not** use the API key filter:

| Route | Method | Description |
|-------|--------|-------------|
| `/faculty-search/teachers`     | GET | Teachers list (optional `faculty_id`, `curriculum_id`, `search`) |
| `/faculty-search/publications/(:num)` | GET | Publications for teacher UID |
| `/faculty-search/faculties`    | GET | Faculties list |
| `/faculty-search/curricula`    | GET | Curricula list |

They are intended for the faculty-search UI and internal AJAX; external systems should use the **`/api/public/...`** endpoints with the **X-API-KEY** header.

---

## Internal API (session login required)

For RR-internal use (logged-in users only):

| Route | Method | Description | Documentation |
|-------|--------|-------------|---------------|
| `/docs` | GET | **Swagger UI** (interactive, like FastAPI) | [CURRICULUM_DETAIL_API.md](./CURRICULUM_DETAIL_API.md) |
| `/api/openapi.json` | GET | OpenAPI 3.0 JSON spec | — |
| `/api/curriculum-detail-by-name` | GET | Curriculum detail (token auth) | [CURRICULUM_DETAIL_API.md](./CURRICULUM_DETAIL_API.md) |

---

## Summary

| Item | Detail |
|------|--------|
| **Controller** | `app/Controllers/ApiController.php` |
| **Routes** | `app/Config/Routes.php` → group `api/public` |
| **Auth** | Header `X-API-KEY`; filter `app/Filters/ApiKeyFilter.php` |
| **Default key** | `URU_RESEARCH` (override via `API_KEY` in `.env`) |
| **Endpoints** | `publications-by-email`, `search-teachers`, `faculty-personnel` |

For implementation details (parameters, response shapes, DB usage), see the corresponding methods in **ApiController**.
