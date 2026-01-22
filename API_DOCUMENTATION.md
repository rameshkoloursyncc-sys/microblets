# Microbelts Inventory API Documentation

## Base URL
```
Production: https://inventory.microbelts.com/api
```

## Authentication
All API endpoints (except login/logout) require session-based authentication.

### Headers Required
```
Content-Type: application/json
Accept: application/json
X-CSRF-TOKEN: {csrf_token}
X-Auth-User: {user_json} // Fallback authentication
```

---

## Authentication Endpoints

### POST /api/login
Login user and create session.

**Request:**
```json
{
  "name": "string",
  "password": "string"
}
```

**Response (200):**
```json
{
  "message": "Login successful",
  "user": {
    "id": 1,
    "name": "username",
    "role": "admin|user"
  }
}
```

**Response (422):**
```json
{
  "message": "The provided credentials are incorrect.",
  "errors": {
    "name": ["The provided credentials are incorrect."]
  }
}
```

### POST /api/logout
Logout user and destroy session.

**Response (200):**
```json
{
  "message": "Logout successful"
}
```

### GET /api/user
Get current authenticated user.

**Response (200):**
```json
{
  "user": {
    "id": 1,
    "name": "username",
    "role": "admin|user"
  }
}
```

**Response (401):**
```json
{
  "message": "Not authenticated"
}
```

### POST /api/users (Admin Only)
Create new user.

**Request:**
```json
{
  "name": "string",
  "password": "string",
  "role": "admin|user"
}
```

**Response (201):**
```json
{
  "message": "User created successfully",
  "user": {
    "id": 2,
    "name": "newuser",
    "role": "user"
  }
}
```

### GET /api/users (Admin Only)
Get all users.

**Response (200):**
```json
{
  "users": [
    {
      "id": 1,
      "name": "admin",
      "role": "admin",
      "created_at": "2024-01-01T00:00:00.000000Z"
    }
  ]
}
```

### DELETE /api/users/{id} (Admin Only)
Delete user.

**Response (200):**
```json
{
  "message": "User deleted successfully"
}
```

---

## Dashboard Endpoints

### GET /api/dashboard/inventory-stats
Get overall inventory statistics.

**Response (200):**
```json
{
  "total_products": 1250,
  "total_stock": 15420,
  "total_value": 2450000.50,
  "low_stock_items": 23,
  "out_of_stock_items": 5,
  "sections": {
    "vee_belts": {
      "total_products": 500,
      "total_stock": 8500,
      "total_value": 1200000.00
    },
    "timing_belts": {
      "total_products": 300,
      "total_stock": 3200,
      "total_value": 800000.00
    }
  }
}
```

### GET /api/dashboard/all-belts-debug
Get debug information for all belt types.

**Response (200):**
```json
{
  "vee_belts": {
    "count": 500,
    "total_stock": 8500,
    "sections": ["A", "B", "C", "D", "E"]
  },
  "timing_belts": {
    "count": 300,
    "total_stock": 3200,
    "types": ["T5", "T10", "XL", "L"]
  }
}
```

---

## Vee Belt Endpoints

### GET /api/vee-belts
Get all vee belts.

**Query Parameters:**
- `section` (optional): Filter by section (A, B, C, etc.)
- `search` (optional): Search by size or section
- `low_stock` (optional): Filter low stock items (true/false)

**Response (200):**
```json
[
  {
    "id": 1,
    "section": "A",
    "size": "25",
    "balance_stock": 150,
    "in_stock": 200,
    "out_stock": 50,
    "reorder_level": 20,
    "rate": "25.50",
    "value": "3825.00",
    "remark": "Good quality",
    "sku": "A-25",
    "category": "A Section",
    "created_by": 1,
    "updated_by": 1,
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T00:00:00.000000Z"
  }
]
```

### GET /api/vee-belts/section/{section}
Get vee belts by section.

**Response (200):** Same as GET /api/vee-belts

### POST /api/vee-belts
Create new vee belt.

**Request:**
```json
{
  "section": "A",
  "size": "26",
  "balance_stock": 100,
  "reorder_level": 15,
  "rate": 26.75,
  "remark": "New product"
}
```

**Response (201):**
```json
{
  "message": "Vee belt created successfully",
  "product": {
    "id": 2,
    "section": "A",
    "size": "26",
    "balance_stock": 100,
    "rate": "26.75",
    "value": "2675.00",
    "sku": "A-26"
  }
}
```

### PUT /api/vee-belts/{id}
Update vee belt.

**Request:**
```json
{
  "balance_stock": 120,
  "rate": 27.00,
  "remark": "Updated price"
}
```

**Response (200):**
```json
{
  "message": "Vee belt updated successfully",
  "product": {
    "id": 1,
    "balance_stock": 120,
    "rate": "27.00",
    "value": "3240.00"
  }
}
```

### DELETE /api/vee-belts/{id}
Delete vee belt.

**Response (200):**
```json
{
  "message": "Vee belt deleted successfully"
}
```

### POST /api/vee-belts/bulk-import
Bulk import vee belts from Excel/CSV.

**Request (multipart/form-data):**
```
file: Excel/CSV file
```

**Response (200):**
```json
{
  "message": "Bulk import completed successfully",
  "imported": 150,
  "updated": 25,
  "errors": []
}
```

### POST /api/vee-belts/in-out
Perform IN/OUT operations on vee belts.

**Request:**
```json
{
  "product_ids": [1, 2, 3],
  "type": "IN|OUT",
  "quantity": 50,
  "description": "Stock replenishment"
}
```

**Response (200):**
```json
{
  "message": "IN/OUT operation completed successfully",
  "affected_products": 3,
  "total_quantity": 150
}
```

### GET /api/vee-belts/{id}/transactions
Get transaction history for specific vee belt.

**Response (200):**
```json
[
  {
    "id": 1,
    "type": "IN",
    "quantity": 50,
    "stock_before": 100,
    "stock_after": 150,
    "rate": "25.50",
    "description": "Stock replenishment",
    "user_id": 1,
    "user": {
      "id": 1,
      "name": "admin"
    },
    "created_at": "2024-01-01T00:00:00.000000Z"
  }
]
```

---

## Timing Belt Endpoints

### GET /api/timing-belts
Get all timing belts.

**Query Parameters:**
- `type` (optional): Filter by type (T5, T10, XL, L, etc.)
- `search` (optional): Search by size or type

**Response (200):**
```json
[
  {
    "id": 1,
    "type": "T5",
    "size": "225",
    "total_mm": 2250,
    "sleeves": 15,
    "rate": "45.00",
    "rate_per_sleeve": "675.00",
    "balance_stock_mm": 1800,
    "balance_stock_sleeves": 12,
    "reorder_level": 500,
    "remark": "High demand item",
    "created_by": 1,
    "updated_by": 1,
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T00:00:00.000000Z"
  }
]
```

### POST /api/timing-belts
Create new timing belt.

**Request:**
```json
{
  "type": "T10",
  "size": "300",
  "total_mm": 3000,
  "sleeves": 20,
  "rate": 55.00,
  "rate_per_sleeve": 1100.00,
  "reorder_level": 600,
  "remark": "New timing belt"
}
```

### POST /api/timing-belts/in-out
Perform IN/OUT operations on timing belts.

**Request:**
```json
{
  "product_ids": [1],
  "action": "IN|OUT",
  "quantity": 300,
  "unit_type": "total_mm|sleeves",
  "description": "Stock adjustment"
}
```

**Response (200):**
```json
{
  "message": "Timing belt operation completed successfully",
  "updated_products": [
    {
      "id": 1,
      "new_stock_mm": 2100,
      "new_stock_sleeves": 14
    }
  ]
}
```

---

## Cogged Belt Endpoints

### GET /api/cogged-belts
Get all cogged belts.

**Response (200):**
```json
[
  {
    "id": 1,
    "section": "3VX",
    "size": "450",
    "balance_stock": 85,
    "rate": "125.00",
    "value": "10625.00",
    "reorder_level": 10,
    "remark": "Premium quality",
    "created_at": "2024-01-01T00:00:00.000000Z"
  }
]
```

### POST /api/cogged-belts/in-out
Perform stock operations.

**Request:**
```json
{
  "product_ids": [1, 2],
  "type": "IN|OUT",
  "quantity": 25
}
```

---

## Poly Belt Endpoints

### GET /api/poly-belts
Get all poly belts.

**Response (200):**
```json
[
  {
    "id": 1,
    "section": "PK",
    "size": "1200",
    "ribs": 450,
    "rate_per_rib": "2.50",
    "total_value": "1125.00",
    "reorder_level": 50,
    "remark": "Multi-rib belt",
    "created_at": "2024-01-01T00:00:00.000000Z"
  }
]
```

### POST /api/poly-belts/in-out
Perform rib-based operations.

**Request:**
```json
{
  "product_ids": [1],
  "type": "IN|OUT",
  "quantity": 100,
  "description": "Rib adjustment"
}
```

---

## TPU Belt Endpoints

### GET /api/tpu-belts
Get all TPU belts.

**Response (200):**
```json
[
  {
    "id": 1,
    "type": "AT10",
    "size": "1500",
    "balance_stock": 75,
    "rate": "185.00",
    "value": "13875.00",
    "reorder_level": 15,
    "remark": "Polyurethane timing belt",
    "created_at": "2024-01-01T00:00:00.000000Z"
  }
]
```

---

## Special Belt Endpoints

### GET /api/special-belts
Get all special belts.

**Response (200):**
```json
[
  {
    "id": 1,
    "type": "Custom",
    "size": "2400x25",
    "balance_stock": 12,
    "rate": "450.00",
    "value": "5400.00",
    "reorder_level": 5,
    "remark": "Custom manufactured belt",
    "created_at": "2024-01-01T00:00:00.000000Z"
  }
]
```

---

## Rate Formula Endpoints

### GET /api/rate-formulas
Get all rate calculation formulas.

**Response (200):**
```json
{
  "vee_belts": {
    "A": "size * 1.05",
    "B": "size * 1.15",
    "C": "size * 1.25"
  },
  "timing_belts": {
    "T5": "size * 0.20",
    "T10": "size * 0.35"
  }
}
```

### PUT /api/rate-formulas
Update rate formulas.

**Request:**
```json
{
  "belt_type": "vee_belts",
  "section": "A",
  "formula": "size * 1.10"
}
```

---

## Error Responses

### 401 Unauthorized
```json
{
  "message": "Session expired. Please login again.",
  "error": "session_expired",
  "redirect": "/login"
}
```

### 403 Forbidden
```json
{
  "message": "Unauthorized. Admin access required."
}
```

### 422 Validation Error
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "field_name": ["Validation error message"]
  }
}
```

### 500 Server Error
```json
{
  "message": "Internal server error",
  "error": "Detailed error message"
}
```

---

## Rate Limiting
- 60 requests per minute per IP
- 1000 requests per hour for authenticated users

## Notes
- All timestamps are in UTC
- Monetary values are in INR (₹)
- Stock quantities are integers
- Rate calculations are automatic based on formulas
- Transaction history is maintained for all operations
- User attribution is tracked for all changes