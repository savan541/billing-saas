import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import DangerButton from '@/Components/DangerButton';

const statusColors = {
    active: 'bg-green-100 text-green-800',
    paused: 'bg-yellow-100 text-yellow-800',
    cancelled: 'bg-red-100 text-red-800',
};

const statusLabels = {
    active: 'Active',
    paused: 'Paused',
    cancelled: 'Cancelled',
};

const frequencyLabels = {
    monthly: 'Monthly',
    quarterly: 'Quarterly',
    yearly: 'Yearly',
};

const invoiceStatusColors = {
    draft: 'bg-gray-100 text-gray-800',
    sent: 'bg-blue-100 text-blue-800',
    paid: 'bg-green-100 text-green-800',
    overdue: 'bg-red-100 text-red-800',
};

const invoiceStatusLabels = {
    draft: 'Draft',
    sent: 'Sent',
    paid: 'Paid',
    overdue: 'Overdue',
};

export default function RecurringInvoiceShow({ recurringInvoice }) {
    // Get currency symbol from client
    const currencySymbol = recurringInvoice.client?.currency_symbol || '$';

    const handlePause = () => {
        router.post(route('recurring-invoices.pause', recurringInvoice.id));
    };

    const handleResume = () => {
        router.post(route('recurring-invoices.resume', recurringInvoice.id));
    };

    const handleCancel = () => {
        if (confirm('Are you sure you want to cancel this recurring invoice?')) {
            router.post(route('recurring-invoices.cancel', recurringInvoice.id));
        }
    };

    const handleDelete = () => {
        if (confirm('Are you sure you want to delete this recurring invoice? This action cannot be undone.')) {
            router.delete(route('recurring-invoices.destroy', recurringInvoice.id));
        }
    };

    return (
        <AuthenticatedLayout>
            <Head title={`Recurring Invoice - ${recurringInvoice.title}`} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 bg-white border-b border-gray-200">
                            {/* Header */}
                            <div className="flex justify-between items-start mb-6">
                                <div>
                                    <h1 className="text-2xl font-semibold text-gray-900">{recurringInvoice.title}</h1>
                                    <p className="mt-1 text-sm text-gray-600">
                                        Recurring invoice template for {recurringInvoice.client?.name}
                                    </p>
                                </div>
                                <div className="flex space-x-2">
                                    <Link href={route('recurring-invoices.edit', recurringInvoice.id)}>
                                        <PrimaryButton>Edit</PrimaryButton>
                                    </Link>
                                    {recurringInvoice.status === 'active' && (
                                        <SecondaryButton onClick={handlePause}>Pause</SecondaryButton>
                                    )}
                                    {recurringInvoice.status === 'paused' && (
                                        <SecondaryButton onClick={handleResume}>Resume</SecondaryButton>
                                    )}
                                    {recurringInvoice.status !== 'cancelled' && (
                                        <SecondaryButton onClick={handleCancel}>Cancel</SecondaryButton>
                                    )}
                                    <DangerButton onClick={handleDelete}>Delete</DangerButton>
                                </div>
                            </div>

                            {/* Recurring Invoice Details */}
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                                <div className="bg-gray-50 p-4 rounded-lg">
                                    <h3 className="text-sm font-medium text-gray-500 mb-2">Client</h3>
                                    <p className="text-lg font-semibold text-gray-900">{recurringInvoice.client?.name}</p>
                                </div>
                                <div className="bg-gray-50 p-4 rounded-lg">
                                    <h3 className="text-sm font-medium text-gray-500 mb-2">Amount</h3>
                                    <p className="text-lg font-semibold text-gray-900">
                                        {currencySymbol}{parseFloat(recurringInvoice.amount).toFixed(2)}
                                    </p>
                                </div>
                                <div className="bg-gray-50 p-4 rounded-lg">
                                    <h3 className="text-sm font-medium text-gray-500 mb-2">Frequency</h3>
                                    <p className="text-lg font-semibold text-gray-900">
                                        {frequencyLabels[recurringInvoice.frequency]}
                                    </p>
                                </div>
                                <div className="bg-gray-50 p-4 rounded-lg">
                                    <h3 className="text-sm font-medium text-gray-500 mb-2">Status</h3>
                                    <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${statusColors[recurringInvoice.status]}`}>
                                        {statusLabels[recurringInvoice.status]}
                                    </span>
                                </div>
                                <div className="bg-gray-50 p-4 rounded-lg">
                                    <h3 className="text-sm font-medium text-gray-500 mb-2">Start Date</h3>
                                    <p className="text-lg font-semibold text-gray-900">
                                        {new Date(recurringInvoice.start_date).toLocaleDateString()}
                                    </p>
                                </div>
                                <div className="bg-gray-50 p-4 rounded-lg">
                                    <h3 className="text-sm font-medium text-gray-500 mb-2">Next Run Date</h3>
                                    <p className="text-lg font-semibold text-gray-900">
                                        {new Date(recurringInvoice.next_run_date).toLocaleDateString()}
                                    </p>
                                </div>
                            </div>

                            {/* Notes */}
                            {recurringInvoice.notes && (
                                <div className="mb-8">
                                    <h3 className="text-lg font-medium text-gray-900 mb-2">Notes</h3>
                                    <div className="bg-gray-50 p-4 rounded-lg">
                                        <p className="text-gray-700 whitespace-pre-wrap">{recurringInvoice.notes}</p>
                                    </div>
                                </div>
                            )}

                            {/* Generated Invoices */}
                            <div>
                                <h3 className="text-lg font-medium text-gray-900 mb-4">
                                    Generated Invoices ({recurringInvoice.invoices?.length || 0})
                                </h3>
                                
                                {recurringInvoice.invoices?.length > 0 ? (
                                    <div className="overflow-x-auto">
                                        <table className="min-w-full divide-y divide-gray-200">
                                            <thead className="bg-gray-50">
                                                <tr>
                                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Invoice Number
                                                    </th>
                                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Issue Date
                                                    </th>
                                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Due Date
                                                    </th>
                                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Total
                                                    </th>
                                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Status
                                                    </th>
                                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Actions
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody className="bg-white divide-y divide-gray-200">
                                                {recurringInvoice.invoices.map((invoice) => (
                                                    <tr key={invoice.id}>
                                                        <td className="px-6 py-4 whitespace-nowrap">
                                                            <Link
                                                                href={route('invoices.show', invoice.id)}
                                                                className="text-indigo-600 hover:text-indigo-900 font-medium"
                                                            >
                                                                {invoice.invoice_number}
                                                            </Link>
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                            {new Date(invoice.issue_date).toLocaleDateString()}
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                            {new Date(invoice.due_date).toLocaleDateString()}
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                            {recurringInvoice.client?.currency_symbol || '$'}{parseFloat(invoice.total).toFixed(2)}
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap">
                                                            <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${invoiceStatusColors[invoice.status]}`}>
                                                                {invoiceStatusLabels[invoice.status]}
                                                            </span>
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                            <Link href={route('invoices.show', invoice.id)}>
                                                                <span className="text-indigo-600 hover:text-indigo-900">View</span>
                                                            </Link>
                                                        </td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                ) : (
                                    <div className="text-center py-8 bg-gray-50 rounded-lg">
                                        <p className="text-gray-500">No invoices have been generated yet.</p>
                                        <p className="text-sm text-gray-400 mt-1">
                                            The next invoice will be generated on {new Date(recurringInvoice.next_run_date).toLocaleDateString()}.
                                        </p>
                                    </div>
                                )}
                            </div>

                            {/* Navigation */}
                            <div className="mt-8 pt-6 border-t border-gray-200">
                                <Link href={route('recurring-invoices.index')}>
                                    <SecondaryButton>← Back to Recurring Invoices</SecondaryButton>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
