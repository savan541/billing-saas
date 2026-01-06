<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\RecurringInvoice;
use App\Services\CurrencyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    protected $currencyService;

    public function __construct(CurrencyService $currencyService)
    {
        $this->currencyService = $currencyService;
    }

    public function __invoke(Request $request)
    {
        $user = $request->user();
        $clientId = $request->get('client_id');
        
        if ($clientId) {
            $metrics = $this->getClientMetrics($clientId, $user->id);
        } else {
            $metrics = $this->getDashboardMetrics($user->id);
        }
        
        $metrics['base_currency'] = $user->getPreferredCurrency();
        $metrics['base_currency_symbol'] = $user->getCurrencySymbol();
        $metrics['clients'] = $this->getClientsList($user->id);
        
        return Inertia::render('Dashboard', [
            'metrics' => $metrics,
            'selectedClientId' => $clientId,
        ]);
    }
    
    protected function getClientsList(int $userId): array
    {
        return Client::where('user_id', $userId)
            ->orderBy('name')
            ->get(['id', 'name', 'currency'])
            ->map(function ($client) {
                $currency = $client->currency instanceof \App\Enums\Currency 
                    ? $client->currency->value 
                    : ($client->currency ?: 'USD');
                return [
                    'id' => $client->id,
                    'name' => $client->name,
                    'currency' => $currency,
                    'currency_symbol' => $this->currencyService->getCurrencySymbol($currency),
                ];
            })
            ->toArray();
    }
    
    private function getClientMetrics(int $clientId, int $userId): array
    {
        $user = Auth::user();
        $baseCurrency = $user->getPreferredCurrency();
        
        $client = Client::where('id', $clientId)
            ->where('user_id', $userId)
            ->with(['invoices' => function($query) {
                $query->select('id', 'client_id', 'total', 'currency', 'status', 'paid_at', 'due_date', 'invoice_number', 'recurring_invoice_id');
            }])
            ->first(['id', 'name', 'currency']);
            
        if (!$client) {
            return $this->getDashboardMetrics($userId);
        }
        
        $clientCurrency = $client->currency instanceof \App\Enums\Currency 
            ? $client->currency->value 
            : ($client->currency ?: 'USD');
        $currencySymbol = $this->currencyService->getCurrencySymbol($clientCurrency);
        $invoices = $client->invoices;
        
        $now = now();
        $currentMonthStart = $now->copy()->startOfMonth();
        $previousMonthStart = $now->copy()->subMonth()->startOfMonth();
        $previousMonthEnd = $now->copy()->subMonth()->endOfMonth();
        
        // Initialize metrics with the same structure as overview
        $metrics = [
            'total_revenue' => 0,
            'current_month_revenue' => 0,
            'previous_month_revenue' => 0,
            'outstanding_amount' => 0,
            'overdue_amount' => 0,
            'mrr' => 0,
            'invoices_sent_count' => 0,
            'invoices_paid_count' => 0,
            'revenue_growth_percentage' => 0,
            'selected_client' => [
                'id' => $client->id,
                'name' => $client->name,
                'currency' => $clientCurrency,
                'currency_symbol' => $currencySymbol,
                'total_revenue' => 0,
                'current_month_revenue' => 0,
                'previous_month_revenue' => 0,
                'outstanding_amount' => 0,
                'overdue_amount' => 0,
                'invoices_sent_count' => 0,
                'invoices_paid_count' => 0,
                'mrr' => 0,
            ],
            'warnings' => [
                'overdue_invoices' => [],
                'outstanding_invoices' => [],
            ],
            'charts' => [
                'revenue_over_time' => $this->getClientRevenueOverTimeData($clientId, $userId, $baseCurrency),
                'top_clients' => [], // Empty for single client view
                'revenue_types' => $this->getClientRevenueTypesData($clientId, $userId, $baseCurrency),
                'overdue_trend' => $this->getClientOverdueTrendData($clientId, $userId, $baseCurrency),
            ],
        ];
        
        foreach ($invoices as $invoice) {
            $invoiceCurrency = $invoice->currency instanceof \App\Enums\Currency 
                ? $invoice->currency->value 
                : ($invoice->currency ?: 'USD');
            $amount = (float) $invoice->total;
            
            // Convert to base currency for all metrics
            $amountInBase = $this->currencyService->convert(
                $amount,
                $invoiceCurrency,
                $baseCurrency,
                $invoice->paid_at ? $invoice->paid_at->toDateString() : null
            );
            
            if ($invoice->status instanceof \App\Enums\InvoiceStatus) {
                $status = $invoice->status->value;
            } else {
                $status = $invoice->status;
            }
            
            if ($status === 'paid') {
                // Debug: Log the paid_at date and current month start for client metrics
                \Log::info('Client Metrics - Invoice ID: ' . $invoice->id . ', Status: ' . $status . ', Paid At: ' . ($invoice->paid_at ? $invoice->paid_at->toDateTimeString() : 'NULL') . ', Current Month Start: ' . $currentMonthStart->toDateTimeString());
                
                // Update both overall and selected client metrics
                $metrics['selected_client']['total_revenue'] += $amountInBase;
                $metrics['total_revenue'] += $amountInBase;
                
                if ($invoice->paid_at && $invoice->paid_at >= $currentMonthStart) {
                    $metrics['selected_client']['current_month_revenue'] += $amountInBase;
                    $metrics['current_month_revenue'] += $amountInBase;
                    \Log::info('Client Metrics - Added to current month revenue: ' . $amountInBase);
                }
                
                if ($invoice->paid_at && $invoice->paid_at >= $previousMonthStart && $invoice->paid_at <= $previousMonthEnd) {
                    $metrics['selected_client']['previous_month_revenue'] += $amountInBase;
                    $metrics['previous_month_revenue'] += $amountInBase;
                }
                
                $metrics['selected_client']['invoices_paid_count']++;
                $metrics['invoices_paid_count']++;
            }
            
            if (in_array($status, ['sent', 'overdue'])) {
                $metrics['selected_client']['outstanding_amount'] += $amountInBase;
                $metrics['outstanding_amount'] += $amountInBase;
                $metrics['selected_client']['invoices_sent_count']++;
                $metrics['invoices_sent_count']++;
                
                if (count($metrics['warnings']['outstanding_invoices']) < 5) {
                    $metrics['warnings']['outstanding_invoices'][] = [
                        'id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                        'amount' => $amountInBase,
                        'currency' => $this->currencyService->getCurrencySymbol($baseCurrency),
                        'currency_code' => $baseCurrency,
                        'due_date' => $invoice->due_date->format('M j, Y'),
                        'status' => $status,
                        'client_name' => $client->name,
                        'days_until_due' => $invoice->due_date->diffInDays($now, false),
                    ];
                }
            }
            
            if ($status === 'overdue') {
                $metrics['selected_client']['overdue_amount'] += $amountInBase;
                $metrics['overdue_amount'] += $amountInBase;
                
                if (count($metrics['warnings']['overdue_invoices']) < 5) {
                    $metrics['warnings']['overdue_invoices'][] = [
                        'id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                        'amount' => $amountInBase,
                        'currency' => $this->currencyService->getCurrencySymbol($baseCurrency),
                        'currency_code' => $baseCurrency,
                        'due_date' => $invoice->due_date->format('M j, Y'),
                        'client_name' => $client->name,
                        'days_overdue' => $invoice->due_date->diffInDays($now),
                    ];
                }
            }
        }
        
        // Calculate MRR for recurring invoices
        $mrr = RecurringInvoice::where('client_id', $client->id)
            ->where('status', 'active')
            ->sum('amount');
            
        // Convert MRR to base currency
        if ($mrr > 0) {
            $mrrInBase = $this->currencyService->convert(
                $mrr,
                $clientCurrency,
                $baseCurrency
            );
            $metrics['selected_client']['mrr'] = $mrrInBase;
            $metrics['mrr'] = $mrrInBase;
        }
        
        // Calculate revenue growth percentage
        $revenueGrowthPercentage = $metrics['previous_month_revenue'] > 0 
            ? (($metrics['current_month_revenue'] - $metrics['previous_month_revenue']) / $metrics['previous_month_revenue']) * 100 
            : 0;
        $metrics['revenue_growth_percentage'] = round($revenueGrowthPercentage, 2);
        
        return $metrics;
    }
    
    private function getDashboardMetrics(int $userId): array
    {
        $user = Auth::user();
        $baseCurrency = $user->getPreferredCurrency();
        $now = now();
        $currentMonthStart = $now->copy()->startOfMonth();
        $previousMonthStart = $now->copy()->subMonth()->startOfMonth();
        $previousMonthEnd = $now->copy()->subMonth()->endOfMonth();
        
        $clients = Client::where('user_id', $userId)
            ->with(['invoices' => function($query) {
                $query->select('id', 'client_id', 'total', 'currency', 'status', 'paid_at', 'due_date', 'invoice_number', 'recurring_invoice_id');
            }])
            ->get(['id', 'name', 'currency']);
            
        $clientsBreakdown = [];
        $metrics = [
            'total_revenue' => 0,
            'current_month_revenue' => 0,
            'previous_month_revenue' => 0,
            'outstanding_amount' => 0,
            'overdue_amount' => 0,
            'mrr' => 0,
            'invoices_sent_count' => 0,
            'invoices_paid_count' => 0,
            'clients_breakdown' => [],
            'warnings' => [
                'overdue_invoices' => [],
                'outstanding_invoices' => [],
            ],
            'charts' => [
                'revenue_over_time' => $this->getRevenueOverTimeData($userId, $baseCurrency),
                'top_clients' => $this->getTopClientsData($userId, $baseCurrency),
                'revenue_types' => $this->getRevenueTypesData($userId, $baseCurrency),
                'overdue_trend' => $this->getOverdueTrendData($userId, $baseCurrency),
            ],
        ];
        
        foreach ($clients as $client) {
            $clientCurrency = $client->currency instanceof \App\Enums\Currency 
    ? $client->currency->value 
    : ($client->currency ?: 'USD');
            $currencySymbol = $this->currencyService->getCurrencySymbol($clientCurrency);
            
            $clientData = [
                'id' => $client->id,
                'name' => $client->name,
                'currency' => $clientCurrency,
                'currency_symbol' => $currencySymbol,
                'total_revenue' => 0,
                'current_month_revenue' => 0,
                'previous_month_revenue' => 0,
                'outstanding_amount' => 0,
                'overdue_amount' => 0,
                'invoices_sent_count' => 0,
                'invoices_paid_count' => 0,
                'mrr' => 0,
            ];
            
            foreach ($client->invoices as $invoice) {
                $invoiceCurrency = $invoice->currency instanceof \App\Enums\Currency 
                    ? $invoice->currency->value 
                    : ($invoice->currency ?: 'USD');
                $amount = (float) $invoice->total;
                
                // Convert to base currency for all metrics
                $amountInBase = $this->currencyService->convert(
                    $amount,
                    $invoiceCurrency,
                    $baseCurrency,
                    $invoice->paid_at ? $invoice->paid_at->toDateString() : null
                );
                
                if ($invoice->status instanceof \App\Enums\InvoiceStatus) {
                $status = $invoice->status->value;
            } else {
                $status = $invoice->status;
            }
            
            if ($status === 'paid') {
                    // Debug: Log the paid_at date and current month start
                    \Log::info('Invoice ID: ' . $invoice->id . ', Status: ' . $status . ', Paid At: ' . ($invoice->paid_at ? $invoice->paid_at->toDateTimeString() : 'NULL') . ', Current Month Start: ' . $currentMonthStart->toDateTimeString());
                    
                    // Always use base currency for totals
                    $clientData['total_revenue'] += $amountInBase;
                    $metrics['total_revenue'] += $amountInBase;
                    
                    if ($invoice->paid_at && $invoice->paid_at >= $currentMonthStart) {
                        $clientData['current_month_revenue'] += $amountInBase;
                        $metrics['current_month_revenue'] += $amountInBase;
                        \Log::info('Added to current month revenue: ' . $amountInBase);
                    }
                    
                    if ($invoice->paid_at && $invoice->paid_at >= $previousMonthStart && $invoice->paid_at <= $previousMonthEnd) {
                        $clientData['previous_month_revenue'] += $amountInBase;
                        $metrics['previous_month_revenue'] += $amountInBase;
                    }
                    
                    $clientData['invoices_paid_count']++;
                    $metrics['invoices_paid_count']++;
                }
                
                if (in_array($status, ['sent', 'overdue'])) {
                    $clientData['outstanding_amount'] += $amountInBase;
                    $metrics['outstanding_amount'] += $amountInBase;
                    $clientData['invoices_sent_count']++;
                    $metrics['invoices_sent_count']++;
                    
                    if (count($metrics['warnings']['outstanding_invoices']) < 5) {
                        $metrics['warnings']['outstanding_invoices'][] = [
                            'id' => $invoice->id,
                            'invoice_number' => $invoice->invoice_number,
                            'amount' => $amountInBase,
                            'currency' => $this->currencyService->getCurrencySymbol($baseCurrency),
                            'currency_code' => $baseCurrency,
                            'due_date' => $invoice->due_date->format('M j, Y'),
                            'status' => $status,
                            'client_name' => $client->name,
                            'days_until_due' => $invoice->due_date->diffInDays($now, false),
                        ];
                    }
                }
                
                if ($status === 'overdue') {
                    $clientData['overdue_amount'] += $amountInBase;
                    $metrics['overdue_amount'] += $amountInBase;
                    
                    if (count($metrics['warnings']['overdue_invoices']) < 5) {
                        $metrics['warnings']['overdue_invoices'][] = [
                            'id' => $invoice->id,
                            'invoice_number' => $invoice->invoice_number,
                            'amount' => $amountInBase,
                            'currency' => $this->currencyService->getCurrencySymbol($baseCurrency),
                            'currency_code' => $baseCurrency,
                            'due_date' => $invoice->due_date->format('M j, Y'),
                            'client_name' => $client->name,
                            'days_overdue' => $invoice->due_date->diffInDays($now),
                        ];
                    }
                }
            }
            
            $clientMrr = RecurringInvoice::where('client_id', $client->id)
                ->where('status', 'active')
                ->sum('amount');
                
            if ($clientMrr > 0) {
                $clientMrrInBase = $this->currencyService->convert(
                    $clientMrr,
                    $clientCurrency,
                    $baseCurrency
                );
                
                $clientData['mrr'] = $clientMrr;
                $metrics['mrr'] += $clientMrrInBase;
            }
            
            $clientsBreakdown[] = $clientData;
        }
        
        $revenueGrowthPercentage = $metrics['previous_month_revenue'] > 0 
            ? (($metrics['current_month_revenue'] - $metrics['previous_month_revenue']) / $metrics['previous_month_revenue']) * 100 
            : 0;
        
        $metrics['revenue_growth_percentage'] = round($revenueGrowthPercentage, 2);
        $metrics['clients_breakdown'] = $clientsBreakdown;
        
        return $metrics;
    }
    
    protected function convertMetricsToBaseCurrency(array $metrics, string $fromCurrency, string $toCurrency): array
    {
        $converted = [];
        $amountFields = [
            'total_revenue',
            'current_month_revenue',
            'previous_month_revenue',
            'outstanding_amount',
            'overdue_amount',
            'mrr',
        ];
        
        foreach ($metrics as $key => $value) {
            if (is_array($value)) {
                $converted[$key] = $this->convertMetricsToBaseCurrency($value, $fromCurrency, $toCurrency);
            } elseif (in_array($key, $amountFields) && is_numeric($value)) {
                $converted[$key] = $this->currencyService->convert($value, $fromCurrency, $toCurrency);
            } else {
                $converted[$key] = $value;
            }
        }
        
        return $converted;
    }
    
    private function getRevenueOverTimeData(int $userId, string $baseCurrency): array
    {
        $months = collect(range(11, 0))->map(function($monthsAgo) {
            return now()->subMonths($monthsAgo)->format('Y-m');
        });
        
        $revenueData = [];
        
        foreach ($months as $month) {
            $monthStart = \Carbon\Carbon::createFromFormat('Y-m', $month)->startOfMonth();
            $monthEnd = \Carbon\Carbon::createFromFormat('Y-m', $month)->endOfMonth();
            
            $revenue = \App\Models\Invoice::where('user_id', $userId)
                ->where('status', 'paid')
                ->where('paid_at', '>=', $monthStart)
                ->where('paid_at', '<=', $monthEnd)
                ->with('client')
                ->get()
                ->sum(function($invoice) use ($baseCurrency) {
                    $invoiceCurrency = $invoice->currency instanceof \App\Enums\Currency 
                        ? $invoice->currency->value 
                        : ($invoice->currency ?: 'USD');
                    return $this->currencyService->convert(
                        (float) $invoice->total,
                        $invoiceCurrency,
                        $baseCurrency,
                        $invoice->paid_at?->toDateString()
                    );
                });
            
            $revenueData[] = [
                'month' => $month,
                'revenue' => round($revenue, 2),
                'label' => \Carbon\Carbon::createFromFormat('Y-m', $month)->format('M Y')
            ];
        }
        
        return $revenueData;
    }
    
    private function getTopClientsData(int $userId, string $baseCurrency): array
    {
        return \App\Models\Client::where('user_id', $userId)
            ->with(['invoices' => function($query) {
                $query->where('status', 'paid');
            }])
            ->get()
            ->map(function($client) use ($baseCurrency) {
                $totalRevenue = $client->invoices->sum(function($invoice) use ($baseCurrency) {
                    $invoiceCurrency = $invoice->currency instanceof \App\Enums\Currency 
                        ? $invoice->currency->value 
                        : ($invoice->currency ?: 'USD');
                    return $this->currencyService->convert(
                        (float) $invoice->total,
                        $invoiceCurrency,
                        $baseCurrency,
                        $invoice->paid_at?->toDateString()
                    );
                });
                
                return [
                    'name' => $client->name,
                    'revenue' => round($totalRevenue, 2)
                ];
            })
            ->filter(function($client) {
                return $client['revenue'] > 0;
            })
            ->sortByDesc('revenue')
            ->take(10)
            ->values()
            ->toArray();
    }
    
    private function getRevenueTypesData(int $userId, string $baseCurrency): array
    {
        $invoices = \App\Models\Invoice::where('user_id', $userId)
            ->where('status', 'paid')
            ->with('client')
            ->get();
        
        $recurringRevenue = 0;
        $oneTimeRevenue = 0;
        
        foreach ($invoices as $invoice) {
            $invoiceCurrency = $invoice->currency instanceof \App\Enums\Currency 
                ? $invoice->currency->value 
                : ($invoice->currency ?: 'USD');
            
            $amount = $this->currencyService->convert(
                (float) $invoice->total,
                $invoiceCurrency,
                $baseCurrency,
                $invoice->paid_at?->toDateString()
            );
            
            if ($invoice->recurring_invoice_id) {
                $recurringRevenue += $amount;
            } else {
                $oneTimeRevenue += $amount;
            }
        }
        
        return [
            [
                'name' => 'Recurring',
                'value' => round($recurringRevenue, 2),
                'color' => '#8b5cf6'
            ],
            [
                'name' => 'One-time',
                'value' => round($oneTimeRevenue, 2),
                'color' => '#3b82f6'
            ]
        ];
    }
    
    private function getOverdueTrendData(int $userId, string $baseCurrency): array
    {
        $months = collect(range(5, 0))->map(function($monthsAgo) {
            return now()->subMonths($monthsAgo)->format('Y-m');
        });
        
        $overdueData = [];
        
        foreach ($months as $month) {
            $monthStart = \Carbon\Carbon::createFromFormat('Y-m', $month)->startOfMonth();
            $monthEnd = \Carbon\Carbon::createFromFormat('Y-m', $month)->endOfMonth();
            
            $overdueAmount = \App\Models\Invoice::where('user_id', $userId)
                ->where('status', 'overdue')
                ->where('due_date', '<=', $monthEnd)
                ->with('client')
                ->get()
                ->sum(function($invoice) use ($baseCurrency) {
                    $invoiceCurrency = $invoice->currency instanceof \App\Enums\Currency 
                        ? $invoice->currency->value 
                        : ($invoice->currency ?: 'USD');
                    return $this->currencyService->convert(
                        (float) $invoice->total,
                        $invoiceCurrency,
                        $baseCurrency
                    );
                });
            
            $overdueData[] = [
                'month' => $month,
                'overdue_amount' => round($overdueAmount, 2),
                'label' => \Carbon\Carbon::createFromFormat('Y-m', $month)->format('M Y')
            ];
        }
        
        return $overdueData;
    }
    
    private function getClientRevenueOverTimeData(int $clientId, int $userId, string $baseCurrency): array
    {
        $months = collect(range(11, 0))->map(function($monthsAgo) {
            return now()->subMonths($monthsAgo)->format('Y-m');
        });
        
        $revenueData = [];
        
        foreach ($months as $month) {
            $monthStart = \Carbon\Carbon::createFromFormat('Y-m', $month)->startOfMonth();
            $monthEnd = \Carbon\Carbon::createFromFormat('Y-m', $month)->endOfMonth();
            
            $revenue = \App\Models\Invoice::where('user_id', $userId)
                ->where('client_id', $clientId)
                ->where('status', 'paid')
                ->where('paid_at', '>=', $monthStart)
                ->where('paid_at', '<=', $monthEnd)
                ->get()
                ->sum(function($invoice) use ($baseCurrency) {
                    $invoiceCurrency = $invoice->currency instanceof \App\Enums\Currency 
                        ? $invoice->currency->value 
                        : ($invoice->currency ?: 'USD');
                    return $this->currencyService->convert(
                        (float) $invoice->total,
                        $invoiceCurrency,
                        $baseCurrency,
                        $invoice->paid_at?->toDateString()
                    );
                });
            
            $revenueData[] = [
                'month' => $month,
                'revenue' => round($revenue, 2),
                'label' => \Carbon\Carbon::createFromFormat('Y-m', $month)->format('M Y')
            ];
        }
        
        return $revenueData;
    }
    
    private function getClientRevenueTypesData(int $clientId, int $userId, string $baseCurrency): array
    {
        $invoices = \App\Models\Invoice::where('user_id', $userId)
            ->where('client_id', $clientId)
            ->where('status', 'paid')
            ->get();
        
        $recurringRevenue = 0;
        $oneTimeRevenue = 0;
        
        foreach ($invoices as $invoice) {
            $invoiceCurrency = $invoice->currency instanceof \App\Enums\Currency 
                ? $invoice->currency->value 
                : ($invoice->currency ?: 'USD');
            
            $amount = $this->currencyService->convert(
                (float) $invoice->total,
                $invoiceCurrency,
                $baseCurrency,
                $invoice->paid_at?->toDateString()
            );
            
            if ($invoice->recurring_invoice_id) {
                $recurringRevenue += $amount;
            } else {
                $oneTimeRevenue += $amount;
            }
        }
        
        return [
            [
                'name' => 'Recurring',
                'value' => round($recurringRevenue, 2),
                'color' => '#8b5cf6'
            ],
            [
                'name' => 'One-time',
                'value' => round($oneTimeRevenue, 2),
                'color' => '#3b82f6'
            ]
        ];
    }
    
    private function getClientOverdueTrendData(int $clientId, int $userId, string $baseCurrency): array
    {
        $months = collect(range(5, 0))->map(function($monthsAgo) {
            return now()->subMonths($monthsAgo)->format('Y-m');
        });
        
        $overdueData = [];
        
        foreach ($months as $month) {
            $monthStart = \Carbon\Carbon::createFromFormat('Y-m', $month)->startOfMonth();
            $monthEnd = \Carbon\Carbon::createFromFormat('Y-m', $month)->endOfMonth();
            
            $overdueAmount = \App\Models\Invoice::where('user_id', $userId)
                ->where('client_id', $clientId)
                ->where('status', 'overdue')
                ->where('due_date', '<=', $monthEnd)
                ->get()
                ->sum(function($invoice) use ($baseCurrency) {
                    $invoiceCurrency = $invoice->currency instanceof \App\Enums\Currency 
                        ? $invoice->currency->value 
                        : ($invoice->currency ?: 'USD');
                    return $this->currencyService->convert(
                        (float) $invoice->total,
                        $invoiceCurrency,
                        $baseCurrency
                    );
                });
            
            $overdueData[] = [
                'month' => $month,
                'overdue_amount' => round($overdueAmount, 2),
                'label' => \Carbon\Carbon::createFromFormat('Y-m', $month)->format('M Y')
            ];
        }
        
        return $overdueData;
    }
}
