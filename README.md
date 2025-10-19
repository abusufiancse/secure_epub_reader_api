php artisan optimize:clear
php artisan route:clear
composer dump-autoload
php artisan route:list


Awesome, Tanvir — here’s the **complete, production-style API documentation** for your EPUB Reader backend. It includes: overview, auth, endpoints, request/response schemas, errors, cURL examples, and a full **OpenAPI (Swagger) spec** you can import into Postman/Insomnia/Swagger UI.

---

# EPUB Reader API — Complete Documentation

**Base URL (local):** `http://127.0.0.1:8000`
**All endpoints are under:** `/api`

## 0) Overview

* **Free books**: readable by anyone (no login required).
* **Paid books**: require login + purchase.
* **Secure delivery**: client downloads **encrypted EPUB blob**, then requests **short-lived CEK** to decrypt locally (AES-256-GCM).
* **Auth**: Laravel Sanctum Personal Access Tokens (Bearer token).

---

## 1) Authentication

### 1.1 Register

**POST** `/api/register`
Create a user and return a **Sanctum token**.

**Request (JSON)**

```json
{
  "name": "Tanvir",
  "email": "abc@gmail.com",
  "password": "12345678"
}
```

**Response `200 OK`**

```json
{
  "token": "SANCTUM_PLAINTEXT_TOKEN",
  "user": {
    "id": 1,
    "name": "Tanvir",
    "email": "abc@gmail.com",
    "created_at": "2025-10-18T05:10:00.000000Z",
    "updated_at": "2025-10-18T05:10:00.000000Z"
  }
}
```

**Errors**

* `422 Unprocessable Entity` (validation)

---

### 1.2 Login

**POST** `/api/login`
Authenticate and return a **Sanctum token**.

**Request**

```json
{
  "email": "abc@gmail.com",
  "password": "12345678"
}
```

**Response `200 OK`**

```json
{
  "token": "SANCTUM_PLAINTEXT_TOKEN",
  "user": { "...": "..." }
}
```

**Errors**

* `401 Unauthorized` (invalid credentials)

**Auth Header (after login/register)**:

```
Authorization: Bearer <SANCTUM_PLAINTEXT_TOKEN>
Content-Type: application/json
```

---

## 2) Ebooks (Public)

### 2.1 List Ebooks

**GET** `/api/ebooks`

**Response `200 OK`**

```json
[
  {
    "id": 1,
    "title": "Demo Book",
    "author": null,
    "is_free": false,
    "price_cents": 19900
  },
  {
    "id": 2,
    "title": "Free Book",
    "author": "Someone",
    "is_free": true,
    "price_cents": 0
  }
]
```

---

### 2.2 Ebook Details

**GET** `/api/ebooks/{id}`

**Response `200 OK`**

```json
{
  "id": 1,
  "title": "Demo Book",
  "author": null,
  "is_free": false,
  "price_cents": 19900,
  "enc_path": "ebooks/68f31d6502d5d.enc",
  "created_at": "2025-10-18T04:53:57.000000Z",
  "updated_at": "2025-10-18T04:53:57.000000Z"
}
```

**Errors**

* `404 Not Found`

---

## 3) Purchase & Secure Access (Requires Auth)

> All endpoints below require:
> `Authorization: Bearer <TOKEN>`

### 3.1 Purchase (MVP)

**POST** `/api/purchase/{id}`
Marks the ebook as purchased by the authenticated user.

**Response `200 OK`**

```json
{ "message": "Purchased" }
```

**Errors**

* `400 Bad Request` (book is free)
* `404 Not Found` (ebook)
* `401 Unauthorized` (missing/invalid token)

---

### 3.2 Issue Short-Lived Access Token

**POST** `/api/ebooks/{id}/issue-token`
Issues a short-lived token that authorizes **download** + **key** calls. If the book is paid, verifies purchase.

**Response `200 OK`**

```json
{
  "downloadUrl": "http://127.0.0.1:8000/api/ebooks/1/download?token=3f7d6a...",
  "keyUrl": "http://127.0.0.1:8000/api/ebooks/1/key?token=3f7d6a...",
  "expiresIn": 300
}
```

**Errors**

* `403 Forbidden` (not purchased for paid book)
* `404 Not Found` (ebook)
* `401 Unauthorized`

---

### 3.3 Download Encrypted EPUB Blob

**GET** `/api/ebooks/{id}/download?token={short_lived_token}`

**Response `200 OK`** (Encrypted Blob)

```json
{
  "v": 1,
  "iv": "base64-IV",
  "tag": "base64-GCM-TAG",
  "data": "base64-CIPHERTEXT"
}
```

**Errors**

* `403 Forbidden` (token invalid/expired)
* `404 Not Found` (ebook)
* `401 Unauthorized`

---

### 3.4 Get Short-Lived CEK

**GET** `/api/ebooks/{id}/key?token={short_lived_token}`

**Response `200 OK`**

```json
{
  "v": 1,
  "cek_b64": "base64-32bytes",
  "ttl": 120
}
```

**Errors**

* `403 Forbidden` (token invalid/expired)
* `404 Not Found`
* `401 Unauthorized`

---

## 4) Data Models

### 4.1 Ebook

| Field       | Type    | Notes                                |
| ----------- | ------- | ------------------------------------ |
| id          | integer | Primary key                          |
| title       | string  |                                      |
| author      | string? | nullable                             |
| is_free     | boolean | free access flag                     |
| price_cents | integer | price in cents                       |
| enc_path    | string  | internal storage path of `.enc` blob |
| created_at  | string  | ISO8601                              |
| updated_at  | string  | ISO8601                              |

### 4.2 Encrypted Blob

| Field | Type   | Notes                |
| ----- | ------ | -------------------- |
| v     | int    | version (1)          |
| iv    | base64 | GCM nonce (12 bytes) |
| tag   | base64 | GCM tag (16 bytes)   |
| data  | base64 | ciphertext           |

### 4.3 Issue-Token Response

| Field       | Type   | Notes                  |
| ----------- | ------ | ---------------------- |
| downloadUrl | string | signed/short-lived URL |
| keyUrl      | string | signed/short-lived URL |
| expiresIn   | int    | seconds                |

### 4.4 Key Response

| Field   | Type   | Notes                 |
| ------- | ------ | --------------------- |
| v       | int    | 1                     |
| cek_b64 | base64 | 32 bytes, AES-256 key |
| ttl     | int    | seconds               |

### 4.5 Error Shape

```json
{
  "message": "Human readable error",
  "errors": { "field": ["detail"] } // present for 422 validation
}
```

---

## 5) cURL Examples

### Register

```bash
curl -X POST http://127.0.0.1:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Tanvir","email":"abc@gmail.com","password":"12345678"}'
```

### Login

```bash
curl -X POST http://127.0.0.1:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"abc@gmail.com","password":"12345678"}'
```

### List Ebooks

```bash
curl -X GET http://127.0.0.1:8000/api/ebooks
```

### Purchase (auth)

```bash
curl -X POST http://127.0.0.1:8000/api/purchase/1 \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Issue Token (auth)

```bash
curl -X POST http://127.0.0.1:8000/api/ebooks/1/issue-token \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Download Encrypted Blob (auth + token)

```bash
curl -X GET "http://127.0.0.1:8000/api/ebooks/1/download?token=XYZ" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Get CEK (auth + token)

```bash
curl -X GET "http://127.0.0.1:8000/api/ebooks/1/key?token=XYZ" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## 6) Status Codes

* `200 OK` — Successful.
* `201 Created` — (not used in MVP).
* `400 Bad Request` — Eg. trying to purchase a free book.
* `401 Unauthorized` — Missing/invalid token; login required.
* `403 Forbidden` — Not entitled or token expired/invalid.
* `404 Not Found` — Ebook or resource missing.
* `422 Unprocessable Entity` — Validation errors.
* `500 Internal Server Error` — Unexpected error.

---

## 7) Security Notes

* **CEK** is short-lived and returned only to authenticated, entitled users.
* **Encrypted blob** can be cached client-side; useless without CEK.
* Prefer **in-memory** rendering; if using temp files, delete on close.
* Add **rate limiting** to `/issue-token` and `/key`.
* Consider device binding and watermarking later.

---

## 8) OpenAPI (Swagger) — import this

You can paste this YAML into Swagger Editor or import into Postman/Insomnia.

```yaml
openapi: 3.0.3
info:
  title: EPUB Reader API
  version: "1.0.0"
  description: Secure EPUB delivery (free/paid) with short-lived CEK (AES-256-GCM)
servers:
  - url: http://127.0.0.1:8000
paths:
  /api/register:
    post:
      summary: Register a user
      requestBody:
        required: true
        content:
          application/json:
            schema:
              $ref: "#/components/schemas/RegisterRequest"
      responses:
        "200":
          description: Registered
          content:
            application/json:
              schema:
                $ref: "#/components/schemas/AuthResponse"
        "422":
          $ref: "#/components/responses/ValidationError"
  /api/login:
    post:
      summary: Login a user
      requestBody:
        required: true
        content:
          application/json:
            schema:
              $ref: "#/components/schemas/LoginRequest"
      responses:
        "200":
          description: Logged in
          content:
            application/json:
              schema:
                $ref: "#/components/schemas/AuthResponse"
        "401":
          $ref: "#/components/responses/Unauthorized"
  /api/ebooks:
    get:
      summary: List ebooks
      responses:
        "200":
          description: List of ebooks
          content:
            application/json:
              schema:
                type: array
                items:
                  $ref: "#/components/schemas/EbookListItem"
  /api/ebooks/{id}:
    get:
      summary: Ebook details
      parameters:
        - in: path
          name: id
          required: true
          schema: { type: integer }
      responses:
        "200":
          description: Ebook details
          content:
            application/json:
              schema:
                $ref: "#/components/schemas/Ebook"
        "404":
          $ref: "#/components/responses/NotFound"
  /api/purchase/{id}:
    post:
      summary: Purchase an ebook
      security: [{ bearerAuth: [] }]
      parameters:
        - in: path
          name: id
          required: true
          schema: { type: integer }
      responses:
        "200":
          description: Purchased
          content:
            application/json:
              schema:
                type: object
                properties:
                  message: { type: string, example: Purchased }
        "400":
          description: Book is free
        "401":
          $ref: "#/components/responses/Unauthorized"
        "404":
          $ref: "#/components/responses/NotFound"
  /api/ebooks/{id}/issue-token:
    post:
      summary: Issue short-lived access token for download/key
      security: [{ bearerAuth: [] }]
      parameters:
        - in: path
          name: id
          required: true
          schema: { type: integer }
      responses:
        "200":
          description: URLs + expiry
          content:
            application/json:
              schema:
                $ref: "#/components/schemas/IssueTokenResponse"
        "401":
          $ref: "#/components/responses/Unauthorized"
        "403":
          $ref: "#/components/responses/Forbidden"
        "404":
          $ref: "#/components/responses/NotFound"
  /api/ebooks/{id}/download:
    get:
      summary: Download encrypted EPUB blob (JSON)
      security: [{ bearerAuth: [] }]
      parameters:
        - in: path
          name: id
          required: true
          schema: { type: integer }
        - in: query
          name: token
          required: true
          schema: { type: string }
      responses:
        "200":
          description: Encrypted blob
          content:
            application/json:
              schema:
                $ref: "#/components/schemas/EncryptedBlob"
        "401":
          $ref: "#/components/responses/Unauthorized"
        "403":
          $ref: "#/components/responses/Forbidden"
        "404":
          $ref: "#/components/responses/NotFound"
  /api/ebooks/{id}/key:
    get:
      summary: Get short-lived CEK
      security: [{ bearerAuth: [] }]
      parameters:
        - in: path
          name: id
          required: true
          schema: { type: integer }
        - in: query
          name: token
          required: true
          schema: { type: string }
      responses:
        "200":
          description: CEK response
          content:
            application/json:
              schema:
                $ref: "#/components/schemas/KeyResponse"
        "401":
          $ref: "#/components/responses/Unauthorized"
        "403":
          $ref: "#/components/responses/Forbidden"
        "404":
          $ref: "#/components/responses/NotFound"
components:
  securitySchemes:
    bearerAuth:
      type: http
      scheme: bearer
  schemas:
    RegisterRequest:
      type: object
      required: [name, email, password]
      properties:
        name: { type: string, example: Tanvir }
        email: { type: string, format: email, example: abc@gmail.com }
        password: { type: string, format: password, example: 12345678 }
    LoginRequest:
      type: object
      required: [email, password]
      properties:
        email: { type: string, format: email, example: abc@gmail.com }
        password: { type: string, format: password, example: 12345678 }
    AuthResponse:
      type: object
      properties:
        token: { type: string }
        user:
          type: object
          properties:
            id: { type: integer }
            name: { type: string }
            email: { type: string, format: email }
            created_at: { type: string, format: date-time }
            updated_at: { type: string, format: date-time }
    EbookListItem:
      type: object
      properties:
        id: { type: integer }
        title: { type: string }
        author: { type: string, nullable: true }
        is_free: { type: boolean }
        price_cents: { type: integer }
    Ebook:
      allOf:
        - $ref: "#/components/schemas/EbookListItem"
        - type: object
          properties:
            enc_path: { type: string }
            created_at: { type: string, format: date-time }
            updated_at: { type: string, format: date-time }
    IssueTokenResponse:
      type: object
      properties:
        downloadUrl: { type: string, example: http://127.0.0.1:8000/api/ebooks/1/download?token=... }
        keyUrl: { type: string, example: http://127.0.0.1:8000/api/ebooks/1/key?token=... }
        expiresIn: { type: integer, example: 300 }
    EncryptedBlob:
      type: object
      properties:
        v: { type: integer, example: 1 }
        iv: { type: string, example: base64-IV }
        tag: { type: string, example: base64-GCM-TAG }
        data: { type: string, example: base64-CIPHERTEXT }
    KeyResponse:
      type: object
      properties:
        v: { type: integer, example: 1 }
        cek_b64: { type: string, example: base64-32bytes }
        ttl: { type: integer, example: 120 }
  responses:
    Unauthorized:
      description: Unauthenticated
      content:
        application/json:
          schema:
            $ref: "#/components/schemas/Error"
    Forbidden:
      description: Forbidden
      content:
        application/json:
          schema:
            $ref: "#/components/schemas/Error"
    NotFound:
      description: Not found
      content:
        application/json:
          schema:
            $ref: "#/components/schemas/Error"
    ValidationError:
      description: Validation error
      content:
        application/json:
          schema:
            $ref: "#/components/schemas/ValidationError"
  schemas:
    Error:
      type: object
      properties:
        message: { type: string }
    ValidationError:
      allOf:
        - $ref: "#/components/schemas/Error"
        - type: object
          properties:
            errors:
              type: object
              additionalProperties:
                type: array
                items: { type: string }
```

---

If you want, I can also generate a **ready-to-import Postman Collection** (JSON) tailored to your local base URL with example bodies and test scripts.

