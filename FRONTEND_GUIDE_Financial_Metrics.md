# 📊 Frontend Integration Guide: Financial Metrics Endpoints

## Overview

This guide explains how to integrate the **Daily Financial Breakdown & Export** endpoints into your frontend application. These endpoints provide paginated dashboard data and streamed CSV exports for performance analytics.

---

## 🔗 Endpoints Summary

| Endpoint | Method | Response | Use Case |
|----------|--------|----------|----------|
| `/api/v1/dashboard/financial-metrics` | GET | JSON (Paginated) | Display analytics dashboard with pagination |
| `/api/v1/performance/daily-financials` | GET | JSON (Paginated) | Alias for dashboard endpoint |
| `/api/v1/dashboard/financial-metrics/export` | GET | CSV (Streamed) | Download financial report as CSV file |
| `/api/v1/performance/daily-financials/export` | GET | CSV (Streamed) | Alias for export endpoint |

---

## 🔐 Authentication & Headers

All endpoints require Bearer token authentication and optional tenant header:

```javascript
const headers = {
  'Authorization': `Bearer ${localStorage.getItem('admin_token')}`,
  'Accept': 'application/json',
  'X-Tenant': 'naijria'  // or 'kenya' — optional, defaults to current tenant
};
```

---

## 📋 Dashboard Endpoint: Paginated JSON Response

### Purpose
Fetch paginated daily financial metrics with ROI trends, watch alerts, and performance indicators.

### Request Parameters

```javascript
const params = {
  from: '2026-08-01',              // Start date (YYYY-MM-DD) — optional, defaults to 30 days ago
  to: '2026-08-31',                // End date (YYYY-MM-DD) — optional, defaults to today
  exchange_rate: 1500,             // Custom USD conversion rate — optional
  page: 1,                         // Page number — optional, default 1
  per_page: 15                     // Items per page (1-100) — optional, default 15
};
```

### Response Structure

```json
{
  "success": true,
  "tenant": "naijria",
  "currency": "NGN",
  "exchange_rate": 1500,
  "from": "2026-08-01",
  "to": "2026-08-31",
  "start_date": "2026-08-01",
  "end_date": "2026-08-31",
  "pagination": {
    "current_page": 1,
    "data": [
      {
        "date": "2026-08-01",
        "subscribers_count": 120,
        "renewals_count": 45,
        "subscribers_by_plan": {
          "daily": 80,
          "weekly": 30,
          "monthly": 10
        },
        "renewals_by_plan": {
          "daily": 25,
          "weekly": 15,
          "monthly": 5
        },
        "daily_revenue": 27000,
        "net_revenue_after_vat": 24975,
        "mtn_share": 12487.5,
        "aggregator_share": 2497.5,
        "wht": 999,
        "balance_before_ncc": 8991,
        "ncc_levy": 249.75,
        "net_balance": 8741.25,
        "yns_net_revenue_local": 6993,
        "vas_sunych_share_local": 1748.25,
        "currency": "NGN",
        "exchange_rate": 1500,
        "yns_net_revenue_usd": 4.66,
        "ads_cost_usd": 2.5,
        "pnl_usd": 2.16,
        "daily_roi": 86.48,
        "daily_roi_display": "86.48%",
        "daily_revenue_variation": null,
        "daily_revenue_variation_display": "—",
        "roi_trend": "Positive",
        "watch_alert": false
      }
    ],
    "first_page_url": "/api/v1/dashboard/financial-metrics?page=1",
    "from": 1,
    "last_page": 3,
    "last_page_url": "/api/v1/dashboard/financial-metrics?page=3",
    "next_page_url": "/api/v1/dashboard/financial-metrics?page=2",
    "path": "/api/v1/dashboard/financial-metrics",
    "per_page": 15,
    "prev_page_url": null,
    "to": 15,
    "total": 31
  },
  "totals": {
    "date": "TOTAL",
    "subscribers_count": 3720,
    "renewals_count": 1395,
    "daily_revenue": 837000,
    "net_revenue_after_vat": 774225,
    "mtn_share": 387112.5,
    "aggregator_share": 77422.5,
    "wht": 30969,
    "balance_before_ncc": 278721,
    "ncc_levy": 7742.25,
    "net_balance": 270978.75,
    "yns_net_revenue_local": 216783,
    "vas_sunych_share_local": 54195.75,
    "currency": "NGN",
    "exchange_rate": 1500,
    "yns_net_revenue_usd": 144.52,
    "ads_cost_usd": 77.5,
    "pnl_usd": 67.02,
    "daily_roi": 86.48,
    "daily_roi_display": "86.48%",
    "daily_revenue_variation": null,
    "daily_revenue_variation_display": "—",
    "roi_trend": null,
    "watch_alert": false
  }
}
```

### Key Response Fields

| Field | Type | Description |
|-------|------|-------------|
| `daily_roi` | float | Raw ROI percentage value |
| `daily_roi_display` | string | Formatted ROI (e.g., "86.48%") |
| `roi_trend` | string\|null | **"Positive"** = ROI higher than yesterday<br/>**"Negative"** = ROI lower than yesterday<br/>**"Watch"** = Alert condition triggered<br/>**null** = No previous day to compare |
| `watch_alert` | boolean | **true** if: ROI dropped 20+ pp OR 3 consecutive negative days<br/>**false** otherwise |
| `daily_revenue_variation` | float\|null | Day-over-day revenue change percentage |
| `pnl_usd` | float | Profit/Loss in USD (YNS Revenue - Ads Cost) |

### Frontend Implementation: React/Vue

#### Using Axios
```javascript
import axios from 'axios';
import { useState, useEffect } from 'react';

function FinancialDashboard() {
  const [data, setData] = useState(null);
  const [page, setPage] = useState(1);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  useEffect(() => {
    fetchMetrics(page);
  }, [page]);

  const fetchMetrics = async (pageNum) => {
    setLoading(true);
    try {
      const response = await axios.get('/api/v1/dashboard/financial-metrics', {
        params: {
          from: '2026-08-01',
          to: '2026-08-31',
          exchange_rate: 1500,
          page: pageNum,
          per_page: 15
        },
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('admin_token')}`,
          'X-Tenant': 'naijria'
        }
      });
      setData(response.data);
      setError(null);
    } catch (err) {
      setError(err.message);
      console.error('Failed to fetch metrics:', err);
    } finally {
      setLoading(false);
    }
  };

  if (loading) return <div>Loading...</div>;
  if (error) return <div>Error: {error}</div>;
  if (!data) return <div>No data</div>;

  return (
    <div>
      <h2>Financial Metrics Dashboard</h2>
      <p>Tenant: {data.tenant} | Currency: {data.currency}</p>
      
      {/* Totals Summary */}
      <div className="summary-box">
        <h3>Total Summary ({data.totals.date})</h3>
        <table>
          <tbody>
            <tr>
              <td>Total Revenue:</td>
              <td>{data.totals.daily_revenue.toLocaleString()} {data.currency}</td>
            </tr>
            <tr>
              <td>YNS Net Revenue (USD):</td>
              <td>${data.totals.yns_net_revenue_usd.toLocaleString()}</td>
            </tr>
            <tr>
              <td>Ads Cost (USD):</td>
              <td>${data.totals.ads_cost_usd.toLocaleString()}</td>
            </tr>
            <tr>
              <td>P&L (USD):</td>
              <td className={data.totals.pnl_usd >= 0 ? 'profit' : 'loss'}>
                ${data.totals.pnl_usd.toLocaleString()}
              </td>
            </tr>
            <tr>
              <td>Overall ROI:</td>
              <td>{data.totals.daily_roi_display}</td>
            </tr>
          </tbody>
        </table>
      </div>

      {/* Daily Records Table */}
      <table className="metrics-table">
        <thead>
          <tr>
            <th>Date</th>
            <th>Subscribers</th>
            <th>Revenue ({data.currency})</th>
            <th>Net USD</th>
            <th>Ads Cost</th>
            <th>ROI</th>
            <th>Trend</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          {data.pagination.data.map((record) => (
            <tr key={record.date}>
              <td>{record.date}</td>
              <td>{record.subscribers_count}</td>
              <td>{record.daily_revenue.toLocaleString()}</td>
              <td>${record.yns_net_revenue_usd.toFixed(2)}</td>
              <td>${record.ads_cost_usd.toFixed(2)}</td>
              <td className={record.daily_roi >= 0 ? 'positive' : 'negative'}>
                {record.daily_roi_display}
              </td>
              <td>
                <span className={`trend trend-${record.roi_trend?.toLowerCase()}`}>
                  {record.roi_trend || '—'}
                </span>
              </td>
              <td>
                {record.watch_alert ? (
                  <span className="alert">⚠️ WATCH</span>
                ) : (
                  <span className="ok">✓ OK</span>
                )}
              </td>
            </tr>
          ))}
        </tbody>
      </table>

      {/* Pagination Controls */}
      <div className="pagination">
        <button 
          disabled={!data.pagination.prev_page_url}
          onClick={() => setPage(page - 1)}
        >
          ← Previous
        </button>
        <span>Page {page} of {data.pagination.last_page}</span>
        <button 
          disabled={!data.pagination.next_page_url}
          onClick={() => setPage(page + 1)}
        >
          Next →
        </button>
      </div>
    </div>
  );
}

export default FinancialDashboard;
```

#### CSS Styling
```css
.summary-box {
  background: #f5f5f5;
  padding: 20px;
  border-radius: 8px;
  margin-bottom: 30px;
}

.metrics-table {
  width: 100%;
  border-collapse: collapse;
  margin: 20px 0;
}

.metrics-table th {
  background: #333;
  color: white;
  padding: 12px;
  text-align: left;
}

.metrics-table td {
  padding: 10px;
  border-bottom: 1px solid #ddd;
}

.metrics-table tr:hover {
  background: #f9f9f9;
}

.trend {
  padding: 4px 8px;
  border-radius: 4px;
  font-weight: bold;
}

.trend-positive {
  background: #d4edda;
  color: #155724;
}

.trend-negative {
  background: #f8d7da;
  color: #721c24;
}

.trend-watch {
  background: #fff3cd;
  color: #856404;
}

.alert {
  color: #ff6b6b;
  font-weight: bold;
}

.ok {
  color: #28a745;
  font-weight: bold;
}

.positive {
  color: #28a745;
}

.negative {
  color: #dc3545;
}

.pagination {
  display: flex;
  gap: 10px;
  align-items: center;
  justify-content: center;
  margin-top: 30px;
}

.pagination button {
  padding: 8px 16px;
  background: #007bff;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

.pagination button:disabled {
  background: #ccc;
  cursor: not-allowed;
}
```

---

## 📥 Export Endpoint: Streamed CSV Download

### Purpose
Download complete financial breakdown as a CSV file optimized for Excel, with all data streamed in 200-row chunks for memory efficiency.

### Request Parameters

```javascript
const params = {
  from: '2026-08-01',           // Start date (YYYY-MM-DD) — optional
  to: '2026-08-31',             // End date (YYYY-MM-DD) — optional
  exchange_rate: 1500           // Custom exchange rate — optional
};
```

### Response Type
- **Content-Type**: `text/csv; charset=UTF-8`
- **Content-Disposition**: `attachment; filename="financial_breakdown_naijria_2026-08-01_to_2026-08-31.csv"`
- **Streaming**: Chunked (200 rows per chunk for memory efficiency)

### CSV Column Structure

```
Date,
Subscribers,
Renewals,
Daily Revenue (NGN),
Net Revenue After 7.5% VAT,
MTN Share 50%,
Aggregator Share 10%,
WHT 4%,
Balance Before NCC,
NCC Levy 1%,
Net Balance,
YNS Net Revenue (NGN) - 80%,
VAS SUNYCH Share (NGN) - 20%,
YNS Net Revenue (USD),
Ads Cost (USD),
P&L (USD),
Daily ROI %,
Daily Revenue Variation %,
Watch Alert (⚠)
```

### ⚠️ Critical: Use `responseType: 'blob'`

The `responseType: 'blob'` is **REQUIRED** for CSV streaming. Without it, the response will not be treated as binary data and the file will not download correctly.

### Frontend Implementation: React

```javascript
import axios from 'axios';

async function downloadFinancialReport(from, to, exchangeRate = null) {
  try {
    const params = {};
    
    if (from) params.from = from;
    if (to) params.to = to;
    if (exchangeRate) params.exchange_rate = exchangeRate;

    // ⚠️ CRITICAL: responseType must be 'blob' for file download
    const response = await axios.get(
      '/api/v1/dashboard/financial-metrics/export',
      {
        params,
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('admin_token')}`,
          'X-Tenant': 'naijria'
        },
        responseType: 'blob'  // ← DO NOT OMIT THIS
      }
    );

    // Create blob and download
    const blob = new Blob([response.data], { type: 'text/csv;charset=utf-8;' });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `financial_report_${from}_to_${to}.csv`);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
  } catch (error) {
    console.error('Download failed:', error);
    alert('Failed to download report. Please try again.');
  }
}

// Example React Component
function ExportButton() {
  const [loading, setLoading] = useState(false);

  const handleExport = async () => {
    setLoading(true);
    try {
      await downloadFinancialReport('2026-08-01', '2026-08-31', 1500);
    } finally {
      setLoading(false);
    }
  };

  return (
    <button onClick={handleExport} disabled={loading}>
      {loading ? 'Downloading...' : '📥 Download CSV Report'}
    </button>
  );
}

export default ExportButton;
```

### Alternative: Using Fetch API

```javascript
async function downloadReportFetch(from, to, exchangeRate = null) {
  try {
    const url = new URL('/api/v1/dashboard/financial-metrics/export', window.location.origin);
    
    if (from) url.searchParams.append('from', from);
    if (to) url.searchParams.append('to', to);
    if (exchangeRate) url.searchParams.append('exchange_rate', exchangeRate);

    const response = await fetch(url, {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('admin_token')}`,
        'X-Tenant': 'naijria'
      }
    });

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const blob = await response.blob();
    const downloadUrl = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = downloadUrl;
    link.setAttribute('download', `financial_report_${from}_to_${to}.csv`);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(downloadUrl);
  } catch (error) {
    console.error('Download failed:', error);
  }
}
```

---

## 🔄 ROI Trend Indicator Guide

The `roi_trend` field provides a quick visual indicator of performance momentum:

### Values Explanation

| Value | Meaning | Visual Indicator | Action |
|-------|---------|-----------------|--------|
| **Positive** | Current ROI > Yesterday's ROI | 📈 Green | Momentum is good, continue current strategy |
| **Negative** | Current ROI < Yesterday's ROI | 📉 Red | Momentum declining, monitor closely |
| **Watch** | Alert condition triggered | ⚠️ Yellow | Immediate attention needed |
| **null** | First day or no data | — | No comparison available |

### Alert Conditions (Watch)

A `watch_alert: true` with `roi_trend: "Watch"` triggers when:

1. **ROI Drop > 20 percentage points** from yesterday
   - Example: Yesterday 80%, Today 55% → Alert
   
2. **3 Consecutive Negative ROI Days**
   - Day 1: -5% ❌
   - Day 2: -3% ❌
   - Day 3: -8% ❌ → Alert triggered

---

## 🛠️ Advanced Use Cases

### Case 1: Real-Time Dashboard with Auto-Refresh

```javascript
function RealtimeDashboard() {
  const [data, setData] = useState(null);

  useEffect(() => {
    // Initial fetch
    fetchMetrics();

    // Auto-refresh every 5 minutes
    const interval = setInterval(fetchMetrics, 5 * 60 * 1000);
    return () => clearInterval(interval);
  }, []);

  const fetchMetrics = async () => {
    try {
      const response = await axios.get('/api/v1/dashboard/financial-metrics', {
        params: {
          from: new Date(Date.now() - 30 * 24 * 60 * 60 * 1000)
            .toISOString()
            .split('T')[0],
          to: new Date().toISOString().split('T')[0],
          page: 1,
          per_page: 15
        },
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('admin_token')}`,
          'X-Tenant': 'naijria'
        }
      });
      setData(response.data);
    } catch (error) {
      console.error('Auto-refresh failed:', error);
    }
  };

  return <div>{/* render data */}</div>;
}
```

### Case 2: Bulk Export with Progress Tracking

```javascript
async function exportWithProgress(from, to) {
  const xhr = new XMLHttpRequest();
  
  xhr.upload.addEventListener('progress', (event) => {
    if (event.lengthComputable) {
      const percentComplete = (event.loaded / event.total) * 100;
      console.log(`Download: ${percentComplete.toFixed(2)}%`);
      // Update progress bar UI here
    }
  });

  xhr.addEventListener('loadend', () => {
    if (xhr.status === 200) {
      const blob = new Blob([xhr.response], { type: 'text/csv' });
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `financial_report_${from}_to_${to}.csv`);
      link.click();
      window.URL.revokeObjectURL(url);
    }
  });

  xhr.open('GET', 
    `/api/v1/dashboard/financial-metrics/export?from=${from}&to=${to}`,
    true
  );
  xhr.setRequestHeader('Authorization', 
    `Bearer ${localStorage.getItem('admin_token')}`
  );
  xhr.setRequestHeader('X-Tenant', 'naijria');
  xhr.responseType = 'arraybuffer';
  xhr.send();
}
```

### Case 3: Multi-Tenant Comparison

```javascript
async function compareTenants(from, to) {
  const tenants = ['naijria', 'kenya'];
  const results = {};

  for (const tenant of tenants) {
    const response = await axios.get('/api/v1/dashboard/financial-metrics', {
      params: { from, to, page: 1, per_page: 100 },
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('admin_token')}`,
        'X-Tenant': tenant
      }
    });
    
    results[tenant] = {
      totalRevenue: response.data.totals.daily_revenue,
      totalROI: response.data.totals.daily_roi,
      currency: response.data.currency,
      recordCount: response.data.pagination.total
    };
  }

  return results;
}
```

---

## ⚠️ Error Handling

```javascript
async function safeApiCall(endpoint, params) {
  try {
    const response = await axios.get(endpoint, {
      params,
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('admin_token')}`,
        'X-Tenant': 'naijria'
      },
      timeout: 30000 // 30 second timeout
    });
    return { success: true, data: response.data };
  } catch (error) {
    if (error.response) {
      // Server responded with error status
      return {
        success: false,
        error: error.response.data?.message || 'Server error',
        status: error.response.status
      };
    } else if (error.request) {
      // Request sent but no response
      return {
        success: false,
        error: 'No response from server. Check your connection.'
      };
    } else {
      return {
        success: false,
        error: error.message || 'Unknown error'
      };
    }
  }
}
```

---

## 📝 Testing in Postman

Import the provided `Financial_Metrics_Dashboard.postman_collection.json` into Postman:

1. **Set Environment Variables:**
   - `baseUrl`: `http://127.0.0.1:8000`
   - `admin_token`: Your JWT bearer token from login

2. **Test Dashboard Endpoint:**
   - Run: `2. Get Daily Financial Metrics (Paginated - Nigeria)`
   - Verify pagination and `roi_trend` field in response

3. **Test Export Endpoint:**
   - Run: `4. Export Daily Financial Breakdown (CSV Download)`
   - Should download CSV file automatically

4. **Test with Custom Exchange Rate:**
   - Modify `exchange_rate` query parameter
   - Verify `yns_net_revenue_usd` calculation changes

---

## 🚀 Summary

| Feature | Dashboard | Export |
|---------|-----------|--------|
| **Response** | JSON | CSV Stream |
| **Use Case** | UI Display | File Download |
| **Pagination** | Yes | No (full range) |
| **Memory Efficient** | Yes (paginated) | Yes (200-row chunks) |
| **exchange_rate** | ✅ Supported | ✅ Supported |
| **roi_trend** | ✅ Included | Not in JSON |
| **Browser Download** | No | Yes (blob) |

---

## 📚 Additional Resources

- **Postman Collection**: `Financial_Metrics_Dashboard.postman_collection.json`
- **API Documentation**: See collection descriptions
- **Backend Controller**: `app/Http/Controllers/V1/FinancialMetricsController.php`
- **Service Layer**: `app/Services/V1/FinancialMetricsService.php`

---

**Last Updated**: 2026-09-01  
**API Version**: v1  
**Status**: Production Ready
