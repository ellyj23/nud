<?php
// This line ensures user authentication and includes your site's header/menu.
require_once 'header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Petty Cash Management — Feza Logistics</title>
  
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="assets/js/email-document.js"></script>
  
  <style>
    /* ==========================================================================
       1. Global Styles & Theming
       ========================================================================== */
    :root {
      --bg-body: #f8f9fc;
      --bg-card: #ffffff;
      --bg-header: #111827;
      --text-primary: #1f2937;
      --text-secondary: #4b5563;
      --text-muted: #6b7280;
      --border-color: #e5e7eb;
      --accent-primary: #4f46e5;
      --accent-primary-hover: #4338ca;
      --accent-secondary: #eef2ff;
      --accent-secondary-hover: #e0e7ff;
      --danger: #ef4444;
      --danger-hover: #dc2626;
      --danger-light: #fee2e2;
      --success: #10b981;
      --success-hover: #059669;
      --success-light: #d1fae5;
      --orange: #f97316;
      --orange-light: #ffedd5;
      --blue: #3b82f6;
      --blue-light: #dbeafe;

      --radius-sm: 6px;
      --radius-md: 10px;
      --radius-lg: 16px;
      --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
      --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
      --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);
      --transition-speed: 0.2s;
    }

    * { box-sizing: border-box; }
    body {
      margin: 0;
      background: var(--bg-body);
      color: var(--text-primary);
      font-family: "Inter", system-ui, -apple-system, sans-serif;
      -webkit-font-smoothing: antialiased;
      -moz-osx-font-smoothing: grayscale;
    }

    /* ==========================================================================
       2. Layout & Structure
       ========================================================================== */
    .page-header {
      background: linear-gradient(135deg, var(--bg-header) 0%, #374151 100%);
      padding: 32px 24px 80px 24px;
      color: white;
    }
    .header-content {
        max-width: 1400px;
        margin: 0 auto;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .header-content .brand { display: flex; gap: 16px; align-items: center; }
    .header-content .logo {
        width: 48px; height: 48px; border-radius: var(--radius-md);
        background: var(--accent-primary);
        display: flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: 20px;
    }
    .header-content h1 { font-size: 28px; font-weight: 700; margin: 0; }
    .header-content .controls { display: flex; gap: 12px; align-items: center; }

    .content-wrapper { max-width: 1400px; margin: 0 auto; padding: 0 24px 32px; transform: translateY(-50px); }
    .dashboard-grid { display: grid; grid-template-columns: repeat(12, 1fr); gap: 24px; }
    .card { background: var(--bg-card); padding: 24px; border-radius: var(--radius-lg); box-shadow: var(--shadow-md); border: 1px solid var(--border-color);}
    .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .card-title { margin: 0; font-size: 18px; font-weight: 600; }
    
    #addTransactionSection { 
      grid-column: 1 / -1; 
      transition: all 0.4s ease-in-out; 
      transform-origin: top; 
      overflow: hidden; 
      max-height: 0;
      padding-top: 0;
      padding-bottom: 0;
      margin-top: -24px;
      opacity: 0;
    }
    #addTransactionSection.is-visible {
      max-height: 500px;
      padding-top: 24px;
      padding-bottom: 24px;
      margin-top: 0;
      opacity: 1;
    }
    
    /* Responsive Grid Layout */
    @media (min-width: 768px) {
        .grid-span-md-4 { grid-column: span 4; }
        .grid-span-md-6 { grid-column: span 6; }
        .grid-span-md-8 { grid-column: span 8; }
    }
    @media (min-width: 1200px) {
        .grid-span-lg-3 { grid-column: span 3; }
        .grid-span-lg-4 { grid-column: span 4; }
        .grid-span-lg-6 { grid-column: span 6; }
        .grid-span-lg-8 { grid-column: span 8; }
    }
    .grid-span-12 { grid-column: 1 / -1; }


    /* ==========================================================================
       3. Components (Buttons, Forms, Table, etc.)
       ========================================================================== */

    .btn {
      display: inline-flex; align-items: center; justify-content: center; gap: 8px;
      background: var(--accent-primary); color: white;
      padding: 10px 16px; border-radius: var(--radius-md); border: none;
      cursor: pointer; font-weight: 600; white-space: nowrap;
      transition: all var(--transition-speed) ease;
      font-size: 14px;
    }
    .btn:hover { background-color: var(--accent-primary-hover); transform: translateY(-1px); box-shadow: var(--shadow-sm); }
    .btn:focus-visible { outline: 2px solid var(--accent-primary); outline-offset: 2px; }
    .btn.secondary { background: var(--accent-secondary); color: var(--accent-primary); }
    .btn.secondary:hover { background-color: var(--accent-secondary-hover); }
    .btn.danger { background-color: var(--danger); }
    .btn.danger:hover { background-color: var(--danger-hover); }
    .btn.success { background-color: var(--success); }
    .btn.success:hover { background-color: var(--success-hover); }
    .btn:disabled { background-color: #9ca3af; cursor: not-allowed; transform: none; box-shadow: none; }
    .btn-sm { padding: 6px 12px; font-size: 13px; border-radius: var(--radius-sm); }
    .btn .icon { width: 16px; height: 16px; }
    
    .filters-card { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
    .filters-card input, .filters-card select, #pcForm input, #pcForm select, #pcForm textarea {
        padding: 10px 12px; border-radius: var(--radius-md); border: 1px solid var(--border-color); font-size: 14px;
        background-color: white; transition: border-color var(--transition-speed), box-shadow var(--transition-speed);
    }
    .filters-card input:focus, .filters-card select:focus, #pcForm input:focus, #pcForm select:focus, #pcForm textarea:focus {
        border-color: var(--accent-primary); box-shadow: 0 0 0 2px var(--accent-secondary); outline: none;
    }
    .filters-card .grow { flex: 1; min-width: 200px; }

    #pcForm { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    #pcForm .full-width { grid-column: 1 / -1; }
    #pcForm label { font-size: 13px; color: var(--text-secondary); margin-bottom: 6px; font-weight: 500; display: block; }
    #pcForm textarea { resize: vertical; min-height: 80px; font-family: inherit; }
    #pcForm .form-actions { grid-column: 1 / -1; display: flex; justify-content: flex-end; gap: 8px; margin-top: 8px; }

    .chart-wrap { min-height: 300px; position: relative; }

    .summary-totals { font-size: 32px; font-weight: 700; line-height: 1.3; }
    .summary-label { font-size: 14px; font-weight: 500; color: var(--text-muted); margin-bottom: 8px; }
    .balance-positive { color: var(--success); }
    .balance-negative { color: var(--danger); }
    
    .table-container { background: var(--bg-card); border-radius: var(--radius-lg); box-shadow: var(--shadow-md); border: 1px solid var(--border-color); overflow: hidden; }
    .table-header { padding: 20px 24px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); }
    .table-wrapper { overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; min-width: 1000px; }
    thead th {
      font-size: 12px; text-align: left; padding: 16px 18px; color: var(--text-muted); 
      border-bottom: 1px solid var(--border-color); text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;
    }
    thead th:first-child, tbody td:first-child { padding-left: 24px; }
    tbody tr { transition: background-color var(--transition-speed); }
    tbody tr:nth-child(even) { background-color: #fcfcfd; }
    tbody tr:hover { background-color: var(--accent-secondary); }
    tbody tr.editing-row { background-color: #fefce8 !important; }
    tbody td { padding: 16px 18px; border-bottom: 1px solid var(--border-color); vertical-align: middle; font-size: 14px; }
    tbody tr:last-child td { border-bottom: none; }
    tbody td.inline-edit-cell { padding: 6px; }
    tbody td.inline-edit-cell input, tbody td.inline-edit-cell select, tbody td.inline-edit-cell textarea {
        width: 100%; padding: 8px; font-size: 13px; border: 1px solid var(--accent-primary);
        border-radius: var(--radius-sm); background-color: var(--bg-card);
    }
    .tx-amount { font-weight: 600; text-align: right; }
    .tx-actions { display: flex; gap: 8px; justify-content: flex-end;}

    .status-badge { padding: 4px 12px; border-radius: 999px; font-weight: 600; font-size: 12px; text-transform: capitalize; }
    .status-badge.debit { background: var(--danger-light); color: var(--danger-hover); }
    .status-badge.credit { background: var(--success-light); color: var(--success-hover); }

    .skeleton-loader td { padding: 16px 18px; }
    .skeleton-loader .skeleton { background: #e5e7eb; border-radius: var(--radius-sm); animation: pulse 1.5s cubic-bezier(0.4, 0, 0.6, 1) infinite; }
    @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
    
    #inactivity-warning {
        position: fixed; top: 20px; left: 50%; transform: translateX(-50%);
        padding: 15px 25px; background-color: var(--bg-header); color: white;
        border-radius: var(--radius-md); z-index: 9999; box-shadow: var(--shadow-lg);
        display: flex; align-items: center; gap: 15px; opacity: 0;
        animation: fade-in 0.3s ease forwards;
    }
    @keyframes fade-in { to { opacity: 1; } }
  </style>
</head>
<body>

  <div style="display: none;">
    <svg xmlns="http://www.w3.org/2000/svg">
      <symbol id="icon-plus" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" /></symbol>
      <symbol id="icon-edit" viewBox="0 0 20 20" fill="currentColor"><path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z" /><path fill-rule="evenodd" d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" clip-rule="evenodd" /></symbol>
      <symbol id="icon-trash" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" /></symbol>
      <symbol id="icon-check" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></symbol>
      <symbol id="icon-x" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></symbol>
      <symbol id="icon-filter" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z" clip-rule="evenodd" /></symbol>
      <symbol id="icon-empty" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79a9 9 0 1 1-11.21-1.95 7 7 0 0 0 7.21 7.21Z"></path></symbol>
      <symbol id="icon-wallet" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" /></symbol>
      <symbol id="icon-print" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5 4v3H4a2 2 0 00-2 2v6a2 2 0 002 2h12a2 2 0 002-2V9a2 2 0 00-2-2h-1V4a2 2 0 00-2-2H7a2 2 0 00-2 2zm8 0H7v3h6V4zm0 8H7V9h6v3z" clip-rule="evenodd" /></symbol>
      <symbol id="icon-email" viewBox="0 0 20 20" fill="currentColor"><path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" /><path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" /></symbol>
    </svg>
  </div>
  
  <header class="page-header">
    <div class="header-content">
      <div class="brand"><div class="logo">💰</div><h1>Petty Cash Management</h1></div>
      <div class="controls">
        <button class="btn secondary" id="printReportBtn"><svg class="icon"><use href="#icon-print"/></svg>Print Report</button>
        <button class="btn secondary" id="emailReportBtn"><svg class="icon"><use href="#icon-email"/></svg>Email Report</button>
        <button class="btn success" id="addMoneyBtn"><svg class="icon"><use href="#icon-plus"/></svg>Add Money</button>
        <button class="btn danger" id="spendMoneyBtn"><svg class="icon"><use href="#icon-wallet"/></svg>Spend Money</button>
      </div>
    </div>
  </header>

  <main class="content-wrapper">
    <div class="dashboard-grid">
      <div id="addTransactionSection" class="card grid-span-12">
        <h3 class="card-title" id="formTitle">Add Transaction</h3>
        <form id="pcForm" autocomplete="off">
          <input type="hidden" id="f_id" name="id" />
          <input type="hidden" id="f_transaction_type" name="transaction_type" />
          
          <div>
            <label for="f_transaction_date">Date *</label>
            <input type="date" id="f_transaction_date" name="transaction_date" required />
          </div>
          
          <div>
            <label for="f_amount">Amount *</label>
            <input type="number" step="0.01" id="f_amount" name="amount" required />
          </div>
          
          <div>
            <label for="f_currency">Currency *</label>
            <select id="f_currency" name="currency" required>
              <option value="RWF">RWF - Rwandan Franc</option>
              <option value="USD">USD - US Dollar</option>
              <option value="EUR">EUR - Euro</option>
              <option value="GBP">GBP - British Pound</option>
            </select>
          </div>
          
          <div class="full-width">
            <label for="f_description">Description/Purpose *</label>
            <textarea id="f_description" name="description" required placeholder="Enter transaction purpose or details"></textarea>
          </div>
          
          <div>
            <label for="f_payment_method">Payment Method</label>
            <select id="f_payment_method" name="payment_method">
              <option value="">-- Select --</option>
              <option>CASH</option>
              <option>BANK</option>
              <option>MTN</option>
              <option>OTHER</option>
            </select>
          </div>
          
          <div>
            <label for="f_reference">Reference</label>
            <input type="text" id="f_reference" name="reference" placeholder="Optional reference number" />
          </div>
          
          <div class="form-actions">
            <button class="btn" type="submit"><svg class="icon"><use href="#icon-check"/></svg>Save</button>
            <button class="btn secondary" type="button" id="cancelAddBtn"><svg class="icon"><use href="#icon-x"/></svg>Cancel</button>
          </div>
        </form>
      </div>

      <div class="card grid-span-12 grid-span-lg-4">
        <div class="summary-label">Current Balance</div>
        <div id="currentBalance" class="summary-totals">—</div>
      </div>
      
      <div class="card grid-span-12 grid-span-lg-4">
        <div class="summary-label">Total Money Added</div>
        <div id="totalCredit" class="summary-totals" style="color: var(--success);">—</div>
      </div>
      
      <div class="card grid-span-12 grid-span-lg-4">
        <div class="summary-label">Total Money Spent</div>
        <div id="totalDebit" class="summary-totals" style="color: var(--danger);">—</div>
      </div>
      
      <div class="card grid-span-12 grid-span-lg-6">
        <h3 class="card-title">Transaction Distribution</h3>
        <div class="chart-wrap"><canvas id="pieChart"></canvas></div>
      </div>
      
      <div class="card grid-span-12 grid-span-lg-6">
        <h3 class="card-title">Monthly Trend</h3>
        <div class="chart-wrap"><canvas id="lineChart"></canvas></div>
      </div>
      
      <div class="filters-card card grid-span-12">
        <input type="date" id="fromDateFilter" title="Start Date"/>
        <input type="date" id="toDateFilter" title="End Date"/>
        <select id="typeFilter" title="Transaction Type">
          <option value="all">All Types</option>
          <option value="credit">Money Added</option>
          <option value="debit">Money Spent</option>
        </select>
        <input type="text" id="searchFilter" placeholder="Search anything..." class="grow" autocomplete="off" />
        <button class="btn secondary" id="applyFilterBtn"><svg class="icon"><use href="#icon-filter"/></svg>Apply</button>
      </div>

      <div class="table-container grid-span-12">
        <div class="table-header">
          <h3 class="card-title">Transaction History (<span id="txCount">0</span>)</h3>
        </div>
        <div class="table-wrapper">
          <table>
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Type</th>
                  <th>Description</th>
                  <th>Payment Method</th>
                  <th>Reference</th>
                  <th style="text-align: right;">Amount</th>
                  <th style="text-align: right;">Actions</th>
                </tr>
              </thead>
              <tbody id="txTableBody"></tbody>
          </table>
        </div>
      </div>
    </div>
  </main>

  <script>
    // --- Inactivity Timer ---
    (function() {
        const LOGOUT_TIME = 5 * 60 * 1000; const WARNING_TIME = 4 * 60 * 1000;
        let logoutTimer, warningTimer;
        const logoutUser = () => { window.location.href = 'logout.php'; };
        const showWarning = () => { if (document.getElementById('inactivity-warning')) return; const warningDiv = document.createElement('div'); warningDiv.id = 'inactivity-warning'; warningDiv.innerHTML = 'You will be logged out in 1 minute due to inactivity. <button id="stayLoggedIn">Stay Logged In</button>'; document.body.appendChild(warningDiv); document.getElementById('stayLoggedIn').onclick = () => resetTimers();};
        const resetTimers = () => { clearTimeout(warningTimer); clearTimeout(logoutTimer); const warningDiv = document.getElementById('inactivity-warning'); if (warningDiv) warningDiv.remove(); warningTimer = setTimeout(showWarning, WARNING_TIME); logoutTimer = setTimeout(logoutUser, LOGOUT_TIME); };
        ['click', 'mousemove', 'keydown', 'scroll'].forEach(event => document.addEventListener(event, resetTimers, true));
        resetTimers();
    })();

    // --- Main Application Logic ---
    document.addEventListener('DOMContentLoaded', () => {
        let allTransactions = [];
        let charts = {};
        const FETCH_URL = 'fetch_petty_cash.php';
        const API_URL = 'add_petty_cash.php';

        const elements = {
            txTableBody: document.getElementById('txTableBody'),
            addMoneyBtn: document.getElementById("addMoneyBtn"),
            spendMoneyBtn: document.getElementById("spendMoneyBtn"),
            addTransactionSection: document.getElementById("addTransactionSection"),
            pcForm: document.getElementById("pcForm"),
            formTitle: document.getElementById("formTitle"),
            cancelAddBtn: document.getElementById("cancelAddBtn"),
            txCount: document.getElementById('txCount'),
            fromDateFilter: document.getElementById('fromDateFilter'),
            toDateFilter: document.getElementById('toDateFilter'),
            typeFilter: document.getElementById('typeFilter'),
            searchFilter: document.getElementById('searchFilter'),
            applyFilterBtn: document.getElementById('applyFilterBtn'),
            currentBalance: document.getElementById('currentBalance'),
            totalCredit: document.getElementById('totalCredit'),
            totalDebit: document.getElementById('totalDebit'),
            printReportBtn: document.getElementById('printReportBtn'),
            emailReportBtn: document.getElementById('emailReportBtn'),
        };
        
        const showSkeletonLoader = (rows = 8) => {
            let skeletonHTML = '';
            for (let i = 0; i < rows; i++) {
                skeletonHTML += `<tr class="skeleton-loader">
                    <td><div class="skeleton" style="width: 80px; height: 16px;"></div></td>
                    <td><div class="skeleton" style="width: 90px; height: 24px; border-radius: 999px;"></div></td>
                    <td><div class="skeleton" style="width: 200px; height: 16px;"></div></td>
                    <td><div class="skeleton" style="width: 60px; height: 16px;"></div></td>
                    <td><div class="skeleton" style="width: 100px; height: 16px;"></div></td>
                    <td style="text-align: right;"><div class="skeleton" style="width: 100px; height: 16px; margin-left: auto;"></div></td>
                    <td style="text-align: right;"><div class="skeleton" style="width: 120px; height: 32px; border-radius: 6px; margin-left: auto;"></div></td>
                </tr>`;
            }
            elements.txTableBody.innerHTML = skeletonHTML;
        };

        const fetchTransactions = async (filters = {}) => {
            showSkeletonLoader();
            try {
                const response = await apiCall('GET', FETCH_URL, filters);
                if (response && response.success) {
                    allTransactions = response.data;
                    renderUI(allTransactions);
                } else {
                    showErrorState('Failed to load transactions. The server returned an error.');
                }
            } catch (error) {
                console.error("Fetch Error:", error);
                showErrorState(`Could not connect to the server. (${error.message})`);
            }
        };
        
        const showErrorState = (message) => {
             elements.txTableBody.innerHTML = `<tr><td colspan="7" style="text-align:center; padding: 60px 24px; color: var(--text-muted);">
              <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" style="margin-bottom: 16px;"><use href="#icon-empty"/></svg>
              <h4 style="margin:0 0 4px;">An Error Occurred</h4>
              <p style="margin:0;">${escapeHtml(message)}</p>
            </td></tr>`;
        }

        const renderUI = (transactions) => {
            cancelEditing();
            renderTable(transactions);
            updateSummary(transactions);
            updateCharts(transactions);
            elements.txCount.textContent = transactions.length;
        };

        const renderTable = (transactions) => {
            elements.txTableBody.innerHTML = '';
            if (transactions.length === 0) {
                elements.txTableBody.innerHTML = `<tr><td colspan="7" style="text-align:center; padding: 60px 24px; color: var(--text-muted);">
                  <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" style="margin-bottom: 16px;"><use href="#icon-empty"/></svg>
                  <h4 style="margin:0 0 4px;">No Transactions Found</h4>
                  <p style="margin:0;">No petty cash transactions yet. Start by adding money or recording an expense.</p>
                </td></tr>`;
                return;
            }
            transactions.forEach(tx => {
                const tr = document.createElement('tr');
                tr.dataset.id = tx.id;
                const currency = tx.currency || 'RWF';
                const currencySymbol = currency === 'USD' ? '$' : currency === 'EUR' ? '€' : currency === 'GBP' ? '£' : currency;
                tr.innerHTML = `
                    <td data-field="transaction_date">${escapeHtml(tx.transaction_date)}</td>
                    <td data-field="transaction_type"><span class="status-badge ${escapeHtml(tx.transaction_type)}">${tx.transaction_type === 'credit' ? 'Money Added' : 'Money Spent'}</span></td>
                    <td data-field="description">${escapeHtml(tx.description)}</td>
                    <td data-field="payment_method">${escapeHtml(tx.payment_method || '—')}</td>
                    <td data-field="reference">${escapeHtml(tx.reference || '—')}</td>
                    <td data-field="amount" class="tx-amount">${tx.transaction_type === 'debit' ? '-' : '+'}${currencySymbol} ${parseFloat(tx.amount).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})}</td>
                    <td class="tx-actions">
                        <button class="btn secondary btn-sm edit-btn"><svg class="icon"><use href="#icon-edit"/></svg>Edit</button>
                        <button class="btn danger btn-sm delete-btn" data-id="${tx.id}"><svg class="icon"><use href="#icon-trash"/></svg>Delete</button>
                    </td>
                `;
                elements.txTableBody.appendChild(tr);
            });
        };
        
        const cancelEditing = () => {
            const editingRow = document.querySelector('tr.editing-row');
            if (editingRow && editingRow.dataset.originalHtml) {
                editingRow.innerHTML = editingRow.dataset.originalHtml;
                editingRow.classList.remove('editing-row');
                delete editingRow.dataset.originalHtml;
            }
        };

        const updateSummary = (transactions) => {
            let totalCredit = 0;
            let totalDebit = 0;
            
            transactions.forEach(tx => {
                const amount = parseFloat(tx.amount);
                if (tx.transaction_type === 'credit') {
                    totalCredit += amount;
                } else if (tx.transaction_type === 'debit') {
                    totalDebit += amount;
                }
            });
            
            const balance = totalCredit - totalDebit;
            const balanceClass = balance >= 0 ? 'balance-positive' : 'balance-negative';
            
            elements.currentBalance.innerHTML = `<span class="${balanceClass}">${balance.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>`;
            elements.totalCredit.textContent = totalCredit.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
            elements.totalDebit.textContent = totalDebit.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
        };

        const updateCharts = (transactions) => {
            destroyCharts();
            if (!transactions.length) return;

            let totalCredit = 0;
            let totalDebit = 0;
            
            transactions.forEach(tx => {
                const amount = parseFloat(tx.amount);
                if (tx.transaction_type === 'credit') totalCredit += amount;
                else if (tx.transaction_type === 'debit') totalDebit += amount;
            });

            const chartOptions = { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { font: { size: 12, family: "Inter" } } } } };
            
            // Pie chart for distribution
            charts.pie = new Chart('pieChart', { 
                type: 'doughnut', 
                data: { 
                    labels: ['Money Added', 'Money Spent'], 
                    datasets: [{ 
                        data: [totalCredit, totalDebit], 
                        backgroundColor: ['rgba(16, 185, 129, 0.9)', 'rgba(239, 68, 68, 0.9)'], 
                        borderColor: 'var(--bg-card)', 
                        borderWidth: 4 
                    }] 
                }, 
                options: chartOptions 
            });
            
            // Line chart for monthly trend
            const monthlyData = {};
            transactions.forEach(tx => {
                const month = tx.transaction_date.substring(0, 7); // YYYY-MM
                if (!monthlyData[month]) {
                    monthlyData[month] = { credit: 0, debit: 0 };
                }
                const amount = parseFloat(tx.amount);
                if (tx.transaction_type === 'credit') {
                    monthlyData[month].credit += amount;
                } else {
                    monthlyData[month].debit += amount;
                }
            });
            
            const sortedMonths = Object.keys(monthlyData).sort();
            const creditData = sortedMonths.map(m => monthlyData[m].credit);
            const debitData = sortedMonths.map(m => monthlyData[m].debit);
            
            charts.line = new Chart('lineChart', {
                type: 'line',
                data: {
                    labels: sortedMonths,
                    datasets: [
                        {
                            label: 'Money Added',
                            data: creditData,
                            borderColor: 'rgba(16, 185, 129, 1)',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            tension: 0.4
                        },
                        {
                            label: 'Money Spent',
                            data: debitData,
                            borderColor: 'rgba(239, 68, 68, 1)',
                            backgroundColor: 'rgba(239, 68, 68, 0.1)',
                            tension: 0.4
                        }
                    ]
                },
                options: { 
                    ...chartOptions, 
                    scales: { y: { beginAtZero: true } } 
                }
            });
        };
        
        async function apiCall(method = 'GET', url = API_URL, data = null) {
            const options = { method, headers: {} };
            let requestUrl = url;
            if (method === 'POST') {
                options.headers['Content-Type'] = 'application/json';
                options.body = JSON.stringify(data);
            } else if (data) {
                requestUrl += '?' + new URLSearchParams(Object.fromEntries(Object.entries(data).filter(([_, v]) => v != null && v !== '')));
            }
            const response = await fetch(requestUrl, options);
            if (!response.ok) {
                const errJson = await response.json().catch(() => ({error: `HTTP error! Status: ${response.status}`}));
                throw new Error(errJson.error || 'An unknown error occurred.');
            }
            return response.json();
        }
        
        const getFilterState = () => ({ 
            from: elements.fromDateFilter.value, 
            to: elements.toDateFilter.value, 
            type: elements.typeFilter.value, 
            q: elements.searchFilter.value.trim() 
        });
        
        // --- Event Listeners ---

        elements.applyFilterBtn.onclick = () => fetchTransactions(getFilterState());
        
        let debounceTimeout;
        elements.searchFilter.addEventListener('input', () => {
            clearTimeout(debounceTimeout);
            debounceTimeout = setTimeout(() => fetchTransactions(getFilterState()), 400);
        });

        elements.txTableBody.addEventListener('click', async (e) => {
            const button = e.target.closest('button');
            if (!button) return;

            if (button.classList.contains('delete-btn')) {
                const id = button.dataset.id;
                if (confirm('Are you sure you want to delete this transaction? This action cannot be undone.')) {
                    const res = await apiCall('POST', API_URL, { action: 'delete', id });
                    if (res && res.success) fetchTransactions(getFilterState());
                }
            }

            if (button.classList.contains('edit-btn')) {
                const tr = button.closest('tr');
                const tx = allTransactions.find(t => t.id == tr.dataset.id);
                if (!tx) return;
                
                cancelEditing();
                tr.dataset.originalHtml = tr.innerHTML;
                tr.classList.add('editing-row');

                tr.cells[0].innerHTML = `<input type="date" name="transaction_date" value="${tx.transaction_date}">`;
                tr.cells[1].innerHTML = `<select name="transaction_type"><option value="credit">Money Added</option><option value="debit">Money Spent</option></select>`;
                tr.cells[2].innerHTML = `<textarea name="description" style="min-height: 60px;">${tx.description}</textarea>`;
                tr.cells[3].innerHTML = `<select name="payment_method"><option value="">--</option><option>CASH</option><option>BANK</option><option>MTN</option><option>OTHER</option></select>`;
                tr.cells[4].innerHTML = `<input type="text" name="reference" value="${tx.reference || ''}">`;
                tr.cells[5].innerHTML = `<input type="number" step="0.01" name="amount" value="${tx.amount}">`;
                tr.cells[6].innerHTML = `<div class="tx-actions"><button class="btn success btn-sm save-btn"><svg class="icon"><use href="#icon-check"/></svg>Save</button><button class="btn secondary btn-sm cancel-edit-btn"><svg class="icon"><use href="#icon-x"/></svg>Cancel</button></div>`;

                tr.querySelector('[name="transaction_type"]').value = tx.transaction_type;
                tr.querySelector('[name="payment_method"]').value = tx.payment_method || '';
                Array.from(tr.cells).slice(0, 6).forEach(cell => cell.classList.add('inline-edit-cell'));
            }

            if (button.classList.contains('cancel-edit-btn')) { cancelEditing(); }

            if (button.classList.contains('save-btn')) {
                const tr = button.closest('tr');
                const id = tr.dataset.id;
                const data = { id, action: 'update' };
                tr.querySelectorAll('input, select, textarea').forEach(input => { data[input.name] = input.value; });
                
                button.disabled = true; button.innerHTML = 'Saving...';
                const response = await apiCall('POST', API_URL, data);
                if (response && response.success) {
                    fetchTransactions(getFilterState());
                } else {
                    alert('Failed to update transaction.');
                    cancelEditing();
                }
            }
        });

        elements.pcForm.addEventListener("submit", async (e) => {
            e.preventDefault();
            const formData = new FormData(elements.pcForm);
            const data = Object.fromEntries(formData.entries());
            data.action = data.id ? 'update' : 'create';

            const submitBtn = elements.pcForm.querySelector('button[type="submit"]');
            submitBtn.disabled = true; submitBtn.innerHTML = 'Saving...';
            
            const response = await apiCall('POST', API_URL, data);
            if (response && response.success) {
                fetchTransactions(getFilterState());
                resetAndHideForm();
            } else {
                alert(response.error || 'Failed to save transaction');
            }
            submitBtn.disabled = false; submitBtn.innerHTML = `<svg class="icon"><use href="#icon-check"/></svg>Save`;
        });

        const resetAndHideForm = () => { 
            elements.pcForm.reset(); 
            document.getElementById('f_id').value = ''; 
            toggleAddForm(false); 
        };
        
        const toggleAddForm = (show, type = 'credit') => {
            if (show) {
                elements.addTransactionSection.classList.add('is-visible');
                elements.addMoneyBtn.style.display = type === 'credit' ? "none" : "inline-flex";
                elements.spendMoneyBtn.style.display = type === 'debit' ? "none" : "inline-flex";
                document.getElementById('f_transaction_type').value = type;
                elements.formTitle.textContent = type === 'credit' ? 'Add Money (Replenish)' : 'Spend Money (Expense)';
                document.getElementById('f_transaction_date').valueAsDate = new Date();
                document.getElementById('f_transaction_date').focus();
            } else {
                elements.addTransactionSection.classList.remove('is-visible');
                elements.addMoneyBtn.style.display = "inline-flex";
                elements.spendMoneyBtn.style.display = "inline-flex";
            }
        };
        
        elements.addMoneyBtn.onclick = () => toggleAddForm(true, 'credit');
        elements.spendMoneyBtn.onclick = () => toggleAddForm(true, 'debit');
        elements.cancelAddBtn.onclick = resetAndHideForm;
        
        // --- Print Report Function ---
        elements.printReportBtn.onclick = () => {
            const filters = getFilterState();
            const dateRange = filters.from && filters.to 
                ? `${filters.from} to ${filters.to}` 
                : (filters.from ? `From ${filters.from}` : (filters.to ? `Until ${filters.to}` : 'All Time'));
            
            // Calculate totals for the current filtered data
            let totalCredit = 0;
            let totalDebit = 0;
            allTransactions.forEach(tx => {
                const amount = parseFloat(tx.amount);
                if (tx.transaction_type === 'credit') totalCredit += amount;
                else if (tx.transaction_type === 'debit') totalDebit += amount;
            });
            const balance = totalCredit - totalDebit;
            
            // Create print window
            const printWindow = window.open('', '', 'height=800,width=1000');
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Petty Cash Report - Feza Logistics</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: white; color: #1f2937; }
                        .header { text-align: center; padding-bottom: 20px; border-bottom: 2px solid #0052cc; margin-bottom: 20px; }
                        .header h1 { color: #0052cc; margin: 0 0 10px 0; font-size: 28px; }
                        .header .company { color: #6b7280; font-size: 14px; }
                        .meta { display: flex; justify-content: space-between; margin-bottom: 20px; padding: 15px; background: #f3f4f6; border-radius: 8px; }
                        .meta-item { text-align: center; }
                        .meta-item .label { font-size: 12px; color: #6b7280; margin-bottom: 4px; }
                        .meta-item .value { font-size: 16px; font-weight: 600; }
                        .summary { display: flex; gap: 20px; margin-bottom: 25px; }
                        .summary-card { flex: 1; padding: 20px; border-radius: 8px; text-align: center; }
                        .summary-card.credit { background: #d1fae5; color: #065f46; }
                        .summary-card.debit { background: #fee2e2; color: #991b1b; }
                        .summary-card.balance { background: #dbeafe; color: #1e40af; }
                        .summary-card .label { font-size: 12px; margin-bottom: 5px; }
                        .summary-card .value { font-size: 24px; font-weight: 700; }
                        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                        th, td { padding: 12px 10px; text-align: left; border-bottom: 1px solid #e5e7eb; }
                        th { background: #f3f4f6; font-weight: 600; color: #374151; font-size: 12px; text-transform: uppercase; }
                        tr:nth-child(even) { background: #f9fafb; }
                        .badge { padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; }
                        .badge-credit { background: #d1fae5; color: #065f46; }
                        .badge-debit { background: #fee2e2; color: #991b1b; }
                        .amount { text-align: right; font-weight: 600; font-family: monospace; }
                        .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb; text-align: center; font-size: 11px; color: #6b7280; }
                        @media print { body { padding: 0; } }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <h1>💰 Petty Cash Report</h1>
                        <div class="company">Feza Logistics Ltd | KN 5 Rd, KG 16 AVe 31, Kigali International Airport, Rwanda</div>
                    </div>
                    
                    <div class="meta">
                        <div class="meta-item">
                            <div class="label">Report Period</div>
                            <div class="value">${escapeHtml(dateRange)}</div>
                        </div>
                        <div class="meta-item">
                            <div class="label">Generated On</div>
                            <div class="value">${new Date().toLocaleString()}</div>
                        </div>
                        <div class="meta-item">
                            <div class="label">Total Transactions</div>
                            <div class="value">${allTransactions.length}</div>
                        </div>
                    </div>
                    
                    <div class="summary">
                        <div class="summary-card credit">
                            <div class="label">Total Money Added</div>
                            <div class="value">+${totalCredit.toLocaleString(undefined, {minimumFractionDigits: 2})}</div>
                        </div>
                        <div class="summary-card debit">
                            <div class="label">Total Money Spent</div>
                            <div class="value">-${totalDebit.toLocaleString(undefined, {minimumFractionDigits: 2})}</div>
                        </div>
                        <div class="summary-card balance">
                            <div class="label">Current Balance</div>
                            <div class="value">${balance.toLocaleString(undefined, {minimumFractionDigits: 2})}</div>
                        </div>
                    </div>
                    
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Description</th>
                                <th>Payment Method</th>
                                <th>Reference</th>
                                <th style="text-align: right;">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${allTransactions.map(tx => {
                                const currency = tx.currency || 'RWF';
                                const currencySymbol = currency === 'USD' ? '$' : currency === 'EUR' ? '€' : currency === 'GBP' ? '£' : currency;
                                return `
                                <tr>
                                    <td>${escapeHtml(tx.transaction_date)}</td>
                                    <td><span class="badge badge-${tx.transaction_type}">${tx.transaction_type === 'credit' ? 'Money Added' : 'Money Spent'}</span></td>
                                    <td>${escapeHtml(tx.description)}</td>
                                    <td>${escapeHtml(tx.payment_method || '—')}</td>
                                    <td>${escapeHtml(tx.reference || '—')}</td>
                                    <td class="amount">${tx.transaction_type === 'debit' ? '-' : '+'}${currencySymbol} ${parseFloat(tx.amount).toLocaleString(undefined, {minimumFractionDigits:2})}</td>
                                </tr>`;
                            }).join('')}
                        </tbody>
                    </table>
                    
                    <div class="footer">
                        <p><strong>Feza Logistics Ltd</strong> | TIN: 121933433 | Phone: (+250) 788 616 117</p>
                        <p>Email: info@fezalogistics.com | Web: www.fezalogistics.com</p>
                        <p style="margin-top: 10px;">This is a system-generated report and does not require a signature.</p>
                    </div>
                </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.focus();
            setTimeout(() => { printWindow.print(); }, 300);
        };
        
        // --- Email Report Function ---
        elements.emailReportBtn.onclick = () => {
            // Open email modal for petty cash report
            if (typeof openEmailModal === 'function') {
                openEmailModal('petty_cash_report', 0, '', '');
            } else {
                alert('Email functionality is being loaded. Please try again.');
            }
        };
        
        // --- Helper Functions ---
        const escapeHtml = (str) => String(str || '').replace(/[&<>"']/g, m => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'})[m]);
        const destroyCharts = () => { Object.values(charts).forEach(chart => chart && chart.destroy()); };
        
        // Initial data load
        fetchTransactions();
    });
  </script>
</body>
</html>
