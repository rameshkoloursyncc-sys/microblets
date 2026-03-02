# Dashboard Snapshot API Usage

## Endpoints

### 1. Get Available Dates
```
GET /api/dashboard/available-dates
```

**Response:**
```json
{
  "success": true,
  "data": {
    "dates": ["2026-03-02", "2026-03-01", "2026-02-28", "2026-02-27", "2026-02-26"],
    "count": 5,
    "latest": "2026-03-02",
    "oldest": "2026-02-26"
  }
}
```

### 2. Get Snapshot Data

#### Single Date
```
GET /api/dashboard/snapshot?date=2026-03-01
```

**Response:**
```json
{
  "success": true,
  "type": "single",
  "data": {
    "snapshot_date": "2026-03-01",
    "finished_goods": {
      "total_products": 2864,
      "in_stock": 1393,
      "low_stock": 12,
      "out_of_stock": 1486,
      "total_value": "40053901.55",
      "categories": {
        "vee_belts": "7150952.82",
        "cogged_belts": "29941374.08",
        ...
      }
    },
    "raw_materials": {
      "total_materials": 162,
      "available": 133,
      "low_stock": 47,
      "out_of_stock": 27,
      "total_value": "17678971.05",
      "categories": {
        "Carbon": "540252.00",
        "Chemical": "605322.40",
        ...
      }
    }
  }
}
```

#### Date Range (Aggregated)
```
GET /api/dashboard/snapshot?start_date=2026-02-27&end_date=2026-03-01
```

**Response:**
```json
{
  "success": true,
  "type": "range",
  "date_range": {
    "start": "2026-02-27",
    "end": "2026-03-01",
    "days": 3
  },
  "data": {
    "snapshot_dates": ["2026-02-27", "2026-02-28", "2026-03-01"],
    "finished_goods": {
      "avg_total_products": 2933,
      "avg_in_stock": 1476,
      "avg_low_stock": 11,
      "avg_out_of_stock": 1505,
      "avg_total_value": 39792369.69,
      "total_value_sum": 119377109.07,
      "categories": { ... }
    },
    "raw_materials": {
      "avg_total_materials": 159,
      "avg_available": 134,
      "avg_low_stock": 44,
      "avg_out_of_stock": 30,
      "avg_total_value": 17722472.75,
      "total_value_sum": 53167418.24,
      "categories": { ... }
    },
    "avg_total_alerts": 55,
    "trend": {
      "finished_goods": {
        "change": -272730.97,
        "percent": -0.68,
        "direction": "down"
      },
      "raw_materials": {
        "change": 279887.60,
        "percent": 1.58,
        "direction": "up"
      }
    }
  }
}
```

#### Default (Today or Latest)
```
GET /api/dashboard/snapshot
```
Returns today's snapshot, or the latest available if today's doesn't exist.

## Use Cases

### 1. Single Day View
Show inventory stats for a specific date:
```javascript
const response = await axios.get('/api/dashboard/snapshot', {
  params: { date: '2026-03-01' }
});
```

### 2. Week View
Show average stats for the past week:
```javascript
const endDate = new Date().toISOString().split('T')[0];
const startDate = new Date(Date.now() - 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];

const response = await axios.get('/api/dashboard/snapshot', {
  params: { start_date: startDate, end_date: endDate }
});
```

### 3. Month View
Show average stats for the current month:
```javascript
const now = new Date();
const startDate = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().split('T')[0];
const endDate = now.toISOString().split('T')[0];

const response = await axios.get('/api/dashboard/snapshot', {
  params: { start_date: startDate, end_date: endDate }
});
```

### 4. Custom Range
Show stats for any custom date range:
```javascript
const response = await axios.get('/api/dashboard/snapshot', {
  params: { 
    start_date: '2026-02-01', 
    end_date: '2026-02-28' 
  }
});
```

## Data Structure

### Single Date Response
- `snapshot_date`: The date of the snapshot
- `finished_goods`: Exact values for that date
- `raw_materials`: Exact values for that date
- `die_requirements`: Die needs for that date
- `total_alerts`: Total alerts for that date

### Date Range Response
- `snapshot_dates`: Array of all dates included
- `date_range`: Start, end, and number of days
- `finished_goods`: Average values across the period
  - `avg_*`: Average values
  - `total_value_sum`: Sum of all values in the period
- `raw_materials`: Average values across the period
- `trend`: Change from first to last day
  - `change`: Absolute change in value
  - `percent`: Percentage change
  - `direction`: "up", "down", or "stable"

## Frontend Implementation

### Date Picker Component
```vue
<template>
  <div class="date-filter">
    <input 
      type="date" 
      v-model="startDate" 
      @change="fetchData"
    />
    <input 
      type="date" 
      v-model="endDate" 
      @change="fetchData"
    />
    <button @click="setToday">Today</button>
    <button @click="setWeek">This Week</button>
    <button @click="setMonth">This Month</button>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';

const startDate = ref('');
const endDate = ref('');
const snapshotData = ref(null);

const fetchData = async () => {
  const params = {};
  
  if (startDate.value && endDate.value) {
    params.start_date = startDate.value;
    params.end_date = endDate.value;
  } else if (startDate.value) {
    params.date = startDate.value;
  }
  
  const response = await axios.get('/api/dashboard/snapshot', { params });
  snapshotData.value = response.data;
};

const setToday = () => {
  const today = new Date().toISOString().split('T')[0];
  startDate.value = today;
  endDate.value = '';
  fetchData();
};

const setWeek = () => {
  const end = new Date();
  const start = new Date(Date.now() - 7 * 24 * 60 * 60 * 1000);
  startDate.value = start.toISOString().split('T')[0];
  endDate.value = end.toISOString().split('T')[0];
  fetchData();
};

const setMonth = () => {
  const now = new Date();
  startDate.value = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().split('T')[0];
  endDate.value = now.toISOString().split('T')[0];
  fetchData();
};
</script>
```

## Notes

- Snapshots are created daily at 00:01 AM
- Date range aggregation calculates averages for all metrics
- Trend analysis shows change from first to last day in the range
- All monetary values are in INR (₹)
- Missing dates in a range are skipped (only existing snapshots are aggregated)
