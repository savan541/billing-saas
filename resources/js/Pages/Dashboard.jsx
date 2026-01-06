import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, usePage, Link, router } from '@inertiajs/react';
import { LineChart, Line, BarChart, Bar, PieChart, Pie, Cell, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer, AreaChart, Area } from 'recharts';
import { TrendingUp, TrendingDown, DollarSign, Users, FileText, AlertTriangle, Clock, CreditCard, ArrowUpRight, ArrowDownRight, AlertCircle, CheckCircle } from 'lucide-react';

export default function Dashboard({ metrics, selectedClientId }) {
    // Get available clients for selector
    const clients = metrics?.clients || [];
    // Format amount with specific currency symbol
    const formatAmountWithSymbol = (amount, symbol) => {
        const numAmount = typeof amount === 'number' ? amount : parseFloat(amount) || 0;
        return `${symbol}${numAmount.toFixed(2)}`;
    };

    // Format amount for display - use base currency from metrics
    const formatAmount = (amount) => {
        if (isNaN(amount) || amount === null || amount === undefined) {
            return '$0.00';
        }
        const currency = metrics?.base_currency || 'USD';
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: currency,
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(amount);
    };

    // Format percentage
    const formatPercentage = (value) => {
        return `${value > 0 ? '+' : ''}${value.toFixed(1)}%`;
    };

    // Trend indicator component
    const TrendIndicator = ({ value }) => {
        const isPositive = value > 0;
        const isNeutral = value === 0;
        
        return (
            <div className={`flex items-center text-sm ${
                isPositive ? 'text-green-600' : isNeutral ? 'text-gray-500' : 'text-red-600'
            }`}>
                <svg 
                    className={`w-4 h-4 mr-1 ${
                        isPositive ? 'rotate-0' : 'rotate-180'
                    }`} 
                    fill="none" 
                    stroke="currentColor" 
                    viewBox="0 0 24 24"
                >
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M7 11l5-5m0 0l5 5m-5-5v12" />
                </svg>
                {formatPercentage(value)}
            </div>
        );
    };

    // Enhanced KPI Card component with visual indicators
    const KpiCard = ({ title, value, trend, icon, iconColor, description, status = 'neutral' }) => {
        const getStatusColors = () => {
            switch (status) {
                case 'positive':
                    return {
                        bg: 'bg-green-50',
                        border: 'border-green-200',
                        text: 'text-green-800',
                        icon: 'text-green-600'
                    };
                case 'warning':
                    return {
                        bg: 'bg-yellow-50',
                        border: 'border-yellow-200',
                        text: 'text-yellow-800',
                        icon: 'text-yellow-600'
                    };
                case 'critical':
                    return {
                        bg: 'bg-red-50',
                        border: 'border-red-200',
                        text: 'text-red-800',
                        icon: 'text-red-600'
                    };
                default:
                    return {
                        bg: 'bg-gray-50',
                        border: 'border-gray-200',
                        text: 'text-gray-800',
                        icon: 'text-gray-600'
                    };
            }
        };

        const colors = getStatusColors();
        
        return (
            <div className={`bg-white overflow-hidden shadow-sm rounded-lg border ${colors.border} hover:shadow-md transition-shadow duration-200`}>
                <div className="p-6">
                    <div className="flex items-center justify-between">
                        <div className="flex-1">
                            <div className="flex items-center">
                                <p className="text-sm font-medium text-gray-500 truncate">{title}</p>
                                {status === 'critical' && (
                                    <AlertCircle className="w-4 h-4 text-red-500 ml-2" />
                                )}
                                {status === 'warning' && (
                                    <Clock className="w-4 h-4 text-yellow-500 ml-2" />
                                )}
                                {status === 'positive' && (
                                    <CheckCircle className="w-4 h-4 text-green-500 ml-2" />
                                )}
                            </div>
                            <p className={`mt-2 text-3xl font-bold ${colors.text}`}>{value}</p>
                            {trend !== undefined && (
                                <div className="mt-2">
                                    <TrendIndicator value={trend} />
                                </div>
                            )}
                            {description && (
                                <p className="mt-1 text-sm text-gray-500">{description}</p>
                            )}
                        </div>
                        <div className={`ml-5 flex-shrink-0`}>
                            <div className={`w-12 h-12 ${iconColor} rounded-md flex items-center justify-center`}>
                                <svg className="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    {icon}
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        );
    };

    // Chart components
    const RevenueChart = () => {
        const data = metrics?.charts?.revenue_over_time || [];
        
        if (data.length === 0) return null;
        
        return (
            <div className="bg-white p-6 rounded-lg shadow">
                <h3 className="text-lg font-semibold text-gray-900 mb-4">Revenue Over Time</h3>
                <ResponsiveContainer width="100%" height={300}>
                    <LineChart data={data}>
                        <CartesianGrid strokeDasharray="3 3" />
                        <XAxis 
                            dataKey="label" 
                            tick={{ fontSize: 12 }}
                            angle={-45}
                            textAnchor="end"
                            height={60}
                        />
                        <YAxis 
                            tick={{ fontSize: 12 }}
                            tickFormatter={(value) => formatAmount(value)}
                        />
                        <Tooltip 
                            formatter={(value) => formatAmount(value)}
                            labelStyle={{ color: '#111827' }}
                        />
                        <Line 
                            type="monotone" 
                            dataKey="revenue" 
                            stroke="#10b981" 
                            strokeWidth={2}
                            dot={{ fill: '#10b981', r: 4 }}
                        />
                    </LineChart>
                </ResponsiveContainer>
            </div>
        );
    };
    
    const TopClientsChart = () => {
        const data = metrics?.charts?.top_clients || [];
        
        if (data.length === 0) return null;
        
        return (
            <div className="bg-white p-6 rounded-lg shadow">
                <h3 className="text-lg font-semibold text-gray-900 mb-4">Top Paying Clients</h3>
                <ResponsiveContainer width="100%" height={300}>
                    <BarChart data={data} layout="horizontal">
                        <CartesianGrid strokeDasharray="3 3" />
                        <XAxis 
                            type="number"
                            tick={{ fontSize: 12 }}
                            tickFormatter={(value) => formatAmount(value)}
                        />
                        <YAxis 
                            type="category"
                            dataKey="name"
                            tick={{ fontSize: 12 }}
                            width={100}
                        />
                        <Tooltip 
                            formatter={(value) => formatAmount(value)}
                            labelStyle={{ color: '#111827' }}
                        />
                        <Bar dataKey="revenue" fill="#3b82f6" />
                    </BarChart>
                </ResponsiveContainer>
            </div>
        );
    };
    
    const RevenueTypesChart = () => {
        const data = metrics?.charts?.revenue_types || [];
        
        if (data.length === 0 || data.every(item => item.value === 0)) return null;
        
        return (
            <div className="bg-white p-6 rounded-lg shadow">
                <h3 className="text-lg font-semibold text-gray-900 mb-4">Revenue Types</h3>
                <ResponsiveContainer width="100%" height={300}>
                    <PieChart>
                        <Pie
                            data={data}
                            cx="50%"
                            cy="50%"
                            labelLine={false}
                            label={(entry) => `${entry.name}: ${formatAmount(entry.value)}`}
                            outerRadius={80}
                            fill="#8884d8"
                            dataKey="value"
                        >
                            {data.map((entry, index) => (
                                <Cell key={`cell-${index}`} fill={entry.color} />
                            ))}
                        </Pie>
                        <Tooltip formatter={(value) => formatAmount(value)} />
                    </PieChart>
                </ResponsiveContainer>
            </div>
        );
    };
    
    const OverdueTrendChart = () => {
        const data = metrics?.charts?.overdue_trend || [];
        
        if (data.length === 0) return null;
        
        return (
            <div className="bg-white p-6 rounded-lg shadow">
                <h3 className="text-lg font-semibold text-gray-900 mb-4">Overdue Trend</h3>
                <ResponsiveContainer width="100%" height={300}>
                    <AreaChart data={data}>
                        <CartesianGrid strokeDasharray="3 3" />
                        <XAxis 
                            dataKey="label" 
                            tick={{ fontSize: 12 }}
                            angle={-45}
                            textAnchor="end"
                            height={60}
                        />
                        <YAxis 
                            tick={{ fontSize: 12 }}
                            tickFormatter={(value) => formatAmount(value)}
                        />
                        <Tooltip 
                            formatter={(value) => formatAmount(value)}
                            labelStyle={{ color: '#111827' }}
                        />
                        <Area 
                            type="monotone" 
                            dataKey="overdue_amount" 
                            stroke="#ef4444" 
                            fill="#fca5a5"
                            strokeWidth={2}
                        />
                    </AreaChart>
                </ResponsiveContainer>
            </div>
        );
    };
    
    // Insights and Warnings component
    const InsightsPanel = () => {
        const insights = [];
        
        // Overdue invoice insights
        if (metrics?.warnings?.overdue_invoices?.length > 0) {
            metrics.warnings.overdue_invoices.forEach(invoice => {
                insights.push({
                    type: 'critical',
                    message: `Client ${invoice.client_name} has overdue invoice ${invoice.invoice_number} of ${formatAmountWithSymbol(invoice.amount, invoice.currency)} (${invoice.days_overdue} days overdue)`,
                    action: 'Follow up immediately'
                });
            });
        }
        
        // High outstanding amount warning
        if (metrics?.outstanding_amount > 10000) {
            insights.push({
                type: 'warning',
                message: `High outstanding amount of ${formatAmount(metrics.outstanding_amount)} requires attention`,
                action: 'Review pending invoices'
            });
        }
        
        // Positive growth insight
        if (metrics?.revenue_growth_percentage > 20) {
            insights.push({
                type: 'positive',
                message: `Strong revenue growth of ${formatPercentage(metrics.revenue_growth_percentage)} this month`,
                action: 'Maintain momentum'
            });
        }
        
        if (insights.length === 0) return null;
        
        return (
            <div className="bg-white p-6 rounded-lg shadow">
                <h3 className="text-lg font-semibold text-gray-900 mb-4">Insights & Actions</h3>
                <div className="space-y-3">
                    {insights.map((insight, index) => (
                        <div key={index} className={`p-4 rounded-lg border-l-4 ${
                            insight.type === 'critical' ? 'bg-red-50 border-red-500' :
                            insight.type === 'warning' ? 'bg-yellow-50 border-yellow-500' :
                            'bg-green-50 border-green-500'
                        }`}>
                            <div className="flex items-start">
                                <div className="flex-shrink-0">
                                    {insight.type === 'critical' && <AlertCircle className="w-5 h-5 text-red-500" />}
                                    {insight.type === 'warning' && <Clock className="w-5 h-5 text-yellow-500" />}
                                    {insight.type === 'positive' && <CheckCircle className="w-5 h-5 text-green-500" />}
                                </div>
                                <div className="ml-3 flex-1">
                                    <p className={`text-sm font-medium ${
                                        insight.type === 'critical' ? 'text-red-800' :
                                        insight.type === 'warning' ? 'text-yellow-800' :
                                        'text-green-800'
                                    }`}>
                                        {insight.message}
                                    </p>
                                    <p className="text-xs text-gray-600 mt-1">
                                        <span className="font-medium">Suggested action:</span> {insight.action}
                                    </p>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        );
    };
    const EmptyState = () => (
        <div className="text-center py-12">
            <svg className="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            <h3 className="mt-2 text-sm font-medium text-gray-900">No data yet</h3>
            <p className="mt-1 text-sm text-gray-500">Start creating invoices to see your billing metrics.</p>
        </div>
    );

    // Check if we have data
    const hasData = metrics && (
        metrics.total_revenue > 0 || 
        metrics.current_month_revenue > 0 || 
        metrics.outstanding_amount > 0 || 
        metrics.overdue_amount > 0 || 
        metrics.mrr > 0 ||
        metrics.invoices_sent_count > 0 ||
        metrics.invoices_paid_count > 0 ||
        (metrics.clients_breakdown && metrics.clients_breakdown.length > 0) ||
        (metrics.selected_client && metrics.selected_client.total_revenue > 0)
    );

    // Handle client selection
    const handleClientChange = (clientId) => {
        if (clientId) {
            router.get('/dashboard', { client_id: clientId });
        } else {
            router.get('/dashboard');
        }
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Billing Dashboard
                </h2>
            }
        >
            <Head title="Billing Dashboard" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    {/* Client Selector */}
                    <div className="mb-6 bg-white overflow-hidden shadow rounded-lg p-4">
                        <label className="block text-sm font-medium text-gray-700 mb-2">
                            Select Client
                        </label>
                        <select
                            value={selectedClientId || ''}
                            onChange={(e) => handleClientChange(e.target.value)}
                            className="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        >
                            <option value="">All Clients</option>
                            {clients?.map((client) => (
                                <option key={client.id} value={client.id}>
                                    {client.name} ({client.currency})
                                </option>
                            ))}
                        </select>
                    </div>

                    {hasData ? (
                        <>
                            {/* Enhanced KPI Cards Grid */}
                            <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                                <KpiCard
                                    title="Total Revenue"
                                    value={formatAmount(metrics.total_revenue)}
                                    trend={metrics.revenue_growth_percentage}
                                    description="All-time from paid invoices"
                                    icon={<path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />}
                                    iconColor="bg-green-500"
                                    status={metrics.total_revenue > 0 ? 'positive' : 'neutral'}
                                />
                                
                                <KpiCard
                                    title="Monthly Revenue"
                                    value={formatAmount(metrics.current_month_revenue)}
                                    trend={metrics.revenue_growth_percentage}
                                    description="This month's paid invoices"
                                    icon={<path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />}
                                    iconColor="bg-blue-500"
                                    status={metrics.current_month_revenue > 0 ? 'positive' : 'neutral'}
                                />
                                
                                <KpiCard
                                    title="Outstanding Amount"
                                    value={formatAmount(metrics.outstanding_amount)}
                                    description="Sent + overdue invoices"
                                    icon={<path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />}
                                    iconColor="bg-yellow-500"
                                    status={metrics.outstanding_amount > 5000 ? 'critical' : metrics.outstanding_amount > 1000 ? 'warning' : 'neutral'}
                                />
                                
                                <KpiCard
                                    title="Overdue Amount"
                                    value={formatAmount(metrics.overdue_amount)}
                                    description="Past due invoices"
                                    icon={<path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />}
                                    iconColor="bg-red-500"
                                    status={metrics.overdue_amount > 0 ? 'critical' : 'neutral'}
                                />
                                
                                <KpiCard
                                    title="Monthly Recurring Revenue"
                                    value={formatAmount(metrics.mrr)}
                                    description="From active recurring invoices"
                                    icon={<path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />}
                                    iconColor="bg-purple-500"
                                    status={metrics.mrr > 0 ? 'positive' : 'neutral'}
                                />
                                
                                <KpiCard
                                    title="Total Clients"
                                    value={metrics.clients?.length || 0}
                                    description="Active client accounts"
                                    icon={<path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />}
                                    iconColor="bg-indigo-500"
                                    status="neutral"
                                />
                            </div>

                            {/* Charts Section */}
                            <div className="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
                                <RevenueChart />
                                <TopClientsChart />
                            </div>
                            
                            <div className="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
                                <RevenueTypesChart />
                                <OverdueTrendChart />
                            </div>

                            {/* Insights Panel */}
                            <div className="mt-8">
                                <InsightsPanel />
                            </div>

                            {/* Client Breakdown */}
                            {selectedClientId && metrics.selected_client && (
                                <div className="mt-8 bg-white overflow-hidden shadow rounded-lg">
                                    <div className="p-6">
                                        <h3 className="text-lg leading-6 font-medium text-gray-900 mb-4">
                                            {metrics.selected_client.name} - {metrics.selected_client.currency} ({metrics.selected_client.currency_symbol})
                                        </h3>
                                        <div className="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                            <div>
                                                <p className="text-gray-500">Total Revenue</p>
                                                <p className="font-medium text-gray-900">
                                                    {formatAmountWithSymbol(metrics.selected_client.total_revenue, metrics.selected_client.currency_symbol)}
                                                </p>
                                            </div>
                                            <div>
                                                <p className="text-gray-500">This Month</p>
                                                <p className="font-medium text-gray-900">
                                                    {formatAmountWithSymbol(metrics.selected_client.current_month_revenue, metrics.selected_client.currency_symbol)}
                                                </p>
                                            </div>
                                            <div>
                                                <p className="text-gray-500">Outstanding</p>
                                                <p className="font-medium text-yellow-600">
                                                    {formatAmountWithSymbol(metrics.selected_client.outstanding_amount, metrics.selected_client.currency_symbol)}
                                                </p>
                                            </div>
                                            <div>
                                                <p className="text-gray-500">MRR</p>
                                                <p className="font-medium text-purple-600">
                                                    {formatAmountWithSymbol(metrics.selected_client.mrr, metrics.selected_client.currency_symbol)}
                                                </p>
                                            </div>
                                            <div>
                                                <p className="text-gray-500">Invoices</p>
                                                <p className="font-medium text-gray-900">
                                                    {metrics.selected_client.invoices_paid_count} paid / {metrics.selected_client.invoices_sent_count} sent
                                                </p>
                                            </div>
                                        </div>
                                        
                                        {metrics.selected_client.overdue_amount > 0 && (
                                            <div className="mt-4 pt-4 border-t border-red-100">
                                                <p className="text-sm text-red-600">
                                                    <span className="font-medium">Overdue:</span> {formatAmountWithSymbol(metrics.selected_client.overdue_amount, metrics.selected_client.currency_symbol)}
                                                </p>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            )}

                            {!selectedClientId && metrics.clients_breakdown && metrics.clients_breakdown.length > 0 && (
                                <div className="mt-8 bg-white overflow-hidden shadow rounded-lg">
                                    <div className="p-6">
                                        <h3 className="text-lg leading-6 font-medium text-gray-900 mb-4">
                                            Revenue by Client
                                        </h3>
                                        <div className="space-y-4">
                                            {metrics.clients_breakdown.map((client) => (
                                                <div key={client.id} className="border border-gray-200 rounded-lg p-4">
                                                    <div className="flex items-center justify-between mb-3">
                                                        <div>
                                                            <h4 className="text-sm font-medium text-gray-900">{client.name}</h4>
                                                            <p className="text-xs text-gray-500">{client.currency} ({client.currency_symbol})</p>
                                                        </div>
                                                        <div className="text-right">
                                                            <p className="text-lg font-bold text-gray-900">
                                                                {formatAmountWithSymbol(client.total_revenue, client.currency_symbol)}
                                                            </p>
                                                            <p className="text-xs text-gray-500">Total Revenue</p>
                                                        </div>
                                                    </div>
                                                    
                                                    <div className="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                                        <div>
                                                            <p className="text-gray-500">This Month</p>
                                                            <p className="font-medium text-gray-900">
                                                                {formatAmountWithSymbol(client.current_month_revenue, client.currency_symbol)}
                                                            </p>
                                                        </div>
                                                        <div>
                                                            <p className="text-gray-500">Outstanding</p>
                                                            <p className="font-medium text-yellow-600">
                                                                {formatAmountWithSymbol(client.outstanding_amount, client.currency_symbol)}
                                                            </p>
                                                        </div>
                                                        <div>
                                                            <p className="text-gray-500">MRR</p>
                                                            <p className="font-medium text-purple-600">
                                                                {formatAmountWithSymbol(client.mrr, client.currency_symbol)}
                                                            </p>
                                                        </div>
                                                        <div>
                                                            <p className="text-gray-500">Invoices</p>
                                                            <p className="font-medium text-gray-900">
                                                                {client.invoices_paid_count} paid / {client.invoices_sent_count} sent
                                                            </p>
                                                        </div>
                                                    </div>
                                                    
                                                    {client.overdue_amount > 0 && (
                                                        <div className="mt-3 pt-3 border-t border-red-100">
                                                            <p className="text-sm text-red-600">
                                                                <span className="font-medium">Overdue:</span> {formatAmountWithSymbol(client.overdue_amount, client.currency_symbol)}
                                                            </p>
                                                        </div>
                                                    )}
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* Warning Sections */}
                            <div className="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
                                {/* Overdue Invoices */}
                                {metrics.warnings.overdue_invoices.length > 0 && (
                                    <div className="bg-white overflow-hidden shadow rounded-lg">
                                        <div className="p-6">
                                            <div className="flex items-center mb-4">
                                                <svg className="w-6 h-6 text-red-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                </svg>
                                                <h3 className="text-lg leading-6 font-medium text-gray-900">
                                                    Overdue Invoices
                                                </h3>
                                            </div>
                                            <div className="space-y-3">
                                                {metrics.warnings.overdue_invoices.map((invoice) => (
                                                    <div key={invoice.id} className="flex items-center justify-between p-3 bg-red-50 rounded-lg">
                                                        <div>
                                                            <p className="text-sm font-medium text-gray-900">{invoice.invoice_number}</p>
                                                            <p className="text-sm text-gray-500">{invoice.client_name}</p>
                                                            <p className="text-xs text-red-600">{invoice.days_overdue} days overdue</p>
                                                        </div>
                                                        <div className="text-right">
                                                            <p className="text-sm font-medium text-gray-900">{formatAmountWithSymbol(invoice.amount, invoice.currency)}</p>
                                                            <p className="text-xs text-gray-500">Due {invoice.due_date}</p>
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    </div>
                                )}

                                {/* Outstanding Invoices */}
                                {metrics.warnings.outstanding_invoices.length > 0 && (
                                    <div className="bg-white overflow-hidden shadow rounded-lg">
                                        <div className="p-6">
                                            <div className="flex items-center mb-4">
                                                <svg className="w-6 h-6 text-yellow-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                <h3 className="text-lg leading-6 font-medium text-gray-900">
                                                    Outstanding Payments
                                                </h3>
                                            </div>
                                            <div className="space-y-3">
                                                {metrics.warnings.outstanding_invoices.map((invoice) => (
                                                    <div key={invoice.id} className={`flex items-center justify-between p-3 rounded-lg ${
                                                        invoice.status === 'overdue' ? 'bg-red-50' : 'bg-yellow-50'
                                                    }`}>
                                                        <div>
                                                            <p className="text-sm font-medium text-gray-900">{invoice.invoice_number}</p>
                                                            <p className="text-sm text-gray-500">{invoice.client_name}</p>
                                                            <p className={`text-xs ${
                                                                invoice.status === 'overdue' ? 'text-red-600' : 'text-yellow-600'
                                                            }`}>
                                                                {invoice.status === 'overdue' 
                                                                    ? `${Math.abs(invoice.days_until_due)} days overdue`
                                                                    : invoice.days_until_due > 0 
                                                                        ? `Due in ${invoice.days_until_due} days`
                                                                        : 'Due today'
                                                                }
                                                            </p>
                                                        </div>
                                                        <div className="text-right">
                                                            <p className="text-sm font-medium text-gray-900">{formatAmountWithSymbol(invoice.amount, invoice.currency)}</p>
                                                            <p className="text-xs text-gray-500">Due {invoice.due_date}</p>
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    </div>
                                )}
                            </div>

                            {/* Invoice Status Breakdown */}
                            <div className="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
                                <div className="bg-white overflow-hidden shadow rounded-lg">
                                    <div className="p-6">
                                        <h3 className="text-lg leading-6 font-medium text-gray-900 mb-4">
                                            Invoice Status Breakdown
                                        </h3>
                                        <div className="space-y-4">
                                            {Object.entries({
                                                draft: { count: metrics.invoices_sent_count, total: 0, label: 'Draft' },
                                                sent: { count: metrics.invoices_sent_count, total: metrics.outstanding_amount, label: 'Sent' },
                                                paid: { count: metrics.invoices_paid_count, total: metrics.total_revenue, label: 'Paid' },
                                                overdue: { count: metrics.warnings.overdue_invoices.length, total: metrics.overdue_amount, label: 'Overdue' },
                                            }).map(([status, data]) => {
                                                if (data.count === 0) return null;
                                                
                                                const statusColors = {
                                                    draft: 'bg-gray-100 text-gray-800',
                                                    sent: 'bg-blue-100 text-blue-800',
                                                    paid: 'bg-green-100 text-green-800',
                                                    overdue: 'bg-red-100 text-red-800',
                                                };
                                                
                                                return (
                                                    <div key={status} className="flex items-center justify-between">
                                                        <div className="flex items-center">
                                                            <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${statusColors[status]}`}>
                                                                {data.label}
                                                            </span>
                                                            <span className="ml-3 text-sm text-gray-500">
                                                                {data.count} {data.count === 1 ? 'invoice' : 'invoices'}
                                                            </span>
                                                        </div>
                                                        <span className="text-sm font-medium text-gray-900">
                                                            {formatAmount(data.total)}
                                                        </span>
                                                    </div>
                                                );
                                            })}
                                            
                                            {(metrics.invoices_sent_count === 0 && metrics.invoices_paid_count === 0 && metrics.warnings.overdue_invoices.length === 0) && (
                                                <div className="text-center py-4 text-gray-500">
                                                    No invoices found
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                </div>

                                {/* Revenue Overview */}
                                <div className="bg-white overflow-hidden shadow rounded-lg">
                                    <div className="p-6">
                                        <h3 className="text-lg leading-6 font-medium text-gray-900 mb-4">
                                            Revenue Overview
                                        </h3>
                                        <div className="space-y-4">
                                            <div className="flex items-center justify-between p-4 bg-green-50 rounded-lg">
                                                <div>
                                                    <p className="text-sm font-medium text-green-800">Total Revenue</p>
                                                    <p className="text-2xl font-bold text-green-900">
                                                        {formatAmount(metrics.total_revenue)}
                                                    </p>
                                                    <p className="text-xs text-green-600 mt-1">
                                                        {metrics.revenue_growth_percentage > 0 ? '+' : ''}{metrics.revenue_growth_percentage}% vs last month
                                                    </p>
                                                </div>
                                                <svg className="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </div>
                                            
                                            <div className="flex items-center justify-between p-4 bg-yellow-50 rounded-lg">
                                                <div>
                                                    <p className="text-sm font-medium text-yellow-800">Outstanding</p>
                                                    <p className="text-2xl font-bold text-yellow-900">
                                                        {formatAmount(metrics.outstanding_amount)}
                                                    </p>
                                                    <p className="text-xs text-yellow-600 mt-1">
                                                        {metrics.invoices_sent_count} awaiting payment
                                                    </p>
                                                </div>
                                                <svg className="w-8 h-8 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </>
                    ) : (
                        <div className="bg-white overflow-hidden shadow rounded-lg">
                            <EmptyState />
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
