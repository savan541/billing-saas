import React, { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';

export default function Edit({ recurringInvoice, clients }) {
    const formatDateForInput = (dateString) => {
        if (!dateString) return '';
        try {
            const date = new Date(dateString);
            if (isNaN(date.getTime())) return '';
            return date.toISOString().split('T')[0];
        } catch (error) {
            return '';
        }
    };

    const { data, setData, put, processing, errors, reset } = useForm({
        client_id: recurringInvoice.client_id.toString(),
        title: recurringInvoice.title,
        amount: recurringInvoice.amount,
        frequency: recurringInvoice.frequency,
        start_date: formatDateForInput(recurringInvoice.start_date),
        next_run_date: formatDateForInput(recurringInvoice.next_run_date),
        status: recurringInvoice.status,
        notes: recurringInvoice.notes || '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        put(route('recurring-invoices.update', recurringInvoice.id), {
            onSuccess: () => reset(),
        });
    };

    const calculateNextRunDate = (startDate, frequency) => {
        const date = new Date(startDate);
        const startDay = date.getDate();
        
        switch (frequency) {
            case 'monthly':
                // Go to next month, try to keep same day
                const nextMonth = date.getMonth() + 1;
                const nextYear = date.getFullYear() + Math.floor(nextMonth / 12);
                const finalMonth = nextMonth % 12;
                
                // Try to set the same day in next month
                const tempDate = new Date(nextYear, finalMonth, startDay);
                
                // If the day doesn't exist in next month, use last day of next month
                if (tempDate.getMonth() !== finalMonth) {
                    date.setFullYear(nextYear, finalMonth + 1, 0);
                } else {
                    // Day exists, keep the same day
                    date.setFullYear(nextYear, finalMonth, startDay);
                }
                break;
            case 'quarterly':
                // Add 3 months, try to keep same day
                const targetQuarterMonth = date.getMonth() + 3;
                const quarterYear = date.getFullYear() + Math.floor(targetQuarterMonth / 12);
                const finalQuarterMonth = targetQuarterMonth % 12;
                
                const tempQuarterDate = new Date(quarterYear, finalQuarterMonth, startDay);
                
                if (tempQuarterDate.getMonth() !== finalQuarterMonth) {
                    date.setFullYear(quarterYear, finalQuarterMonth + 1, 0);
                } else {
                    date.setFullYear(quarterYear, finalQuarterMonth, startDay);
                }
                break;
            case 'yearly':
                // Add 1 year, keeping same day and month
                date.setFullYear(date.getFullYear() + 1);
                break;
        }
        
        return date.toISOString().split('T')[0];
    };

    const handleFrequencyChange = (frequency) => {
        setData('frequency', frequency);
        
        // Auto-calculate next run date based on frequency
        if (data.start_date) {
            const nextRun = calculateNextRunDate(data.start_date, frequency);
            setData('next_run_date', nextRun);
        }
    };

    const handleStartDateChange = (startDate) => {
        setData('start_date', startDate);
        
        // Auto-calculate next run date when start date changes
        if (startDate) {
            const nextRun = calculateNextRunDate(startDate, data.frequency);
            setData('next_run_date', nextRun);
        }
    };

    return (
        <AuthenticatedLayout>
            <Head title="Edit Recurring Invoice" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 bg-white border-b border-gray-200">
                            <div className="mb-6">
                                <h1 className="text-2xl font-semibold text-gray-900">Edit Recurring Invoice</h1>
                                <p className="mt-1 text-sm text-gray-600">
                                    Update the settings for this recurring invoice template.
                                </p>
                            </div>

                            <form onSubmit={handleSubmit} className="space-y-6">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    {/* Client Selection */}
                                    <div>
                                        <InputLabel htmlFor="client_id" value="Client" />
                                        <select
                                            id="client_id"
                                            name="client_id"
                                            value={data.client_id}
                                            onChange={(e) => setData('client_id', e.target.value)}
                                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                        >
                                            <option value="">Select a client</option>
                                            {clients.map((client) => (
                                                <option key={client.id} value={client.id}>
                                                    {client.name}
                                                </option>
                                            ))}
                                        </select>
                                        <InputError message={errors.client_id} className="mt-2" />
                                    </div>

                                    {/* Title */}
                                    <div>
                                        <InputLabel htmlFor="title" value="Title" />
                                        <TextInput
                                            id="title"
                                            name="title"
                                            type="text"
                                            value={data.title}
                                            onChange={(e) => setData('title', e.target.value)}
                                            className="mt-1 block w-full"
                                            placeholder="e.g., Monthly Retainer"
                                        />
                                        <InputError message={errors.title} className="mt-2" />
                                    </div>

                                    {/* Amount */}
                                    <div>
                                        <InputLabel htmlFor="amount" value="Amount" />
                                        <TextInput
                                            id="amount"
                                            name="amount"
                                            type="number"
                                            step="0.01"
                                            min="0.01"
                                            value={data.amount}
                                            onChange={(e) => setData('amount', e.target.value)}
                                            className="mt-1 block w-full"
                                            placeholder="0.00"
                                        />
                                        <InputError message={errors.amount} className="mt-2" />
                                    </div>

                                    {/* Frequency */}
                                    <div>
                                        <InputLabel htmlFor="frequency" value="Frequency" />
                                        <select
                                            id="frequency"
                                            name="frequency"
                                            value={data.frequency}
                                            onChange={(e) => handleFrequencyChange(e.target.value)}
                                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                        >
                                            <option value="monthly">Monthly</option>
                                            <option value="quarterly">Quarterly</option>
                                            <option value="yearly">Yearly</option>
                                        </select>
                                        <InputError message={errors.frequency} className="mt-2" />
                                    </div>

                                    {/* Start Date */}
                                    <div>
                                        <InputLabel htmlFor="start_date" value="Start Date" />
                                        <TextInput
                                            id="start_date"
                                            name="start_date"
                                            type="date"
                                            value={data.start_date}
                                            onChange={(e) => handleStartDateChange(e.target.value)}
                                            className="mt-1 block w-full"
                                        />
                                        <InputError message={errors.start_date} className="mt-2" />
                                    </div>

                                    {/* Next Run Date */}
                                    <div>
                                        <InputLabel htmlFor="next_run_date" value="Next Run Date" />
                                        <TextInput
                                            id="next_run_date"
                                            name="next_run_date"
                                            type="date"
                                            value={data.next_run_date}
                                            onChange={(e) => setData('next_run_date', e.target.value)}
                                            className="mt-1 block w-full"
                                        />
                                        <InputError message={errors.next_run_date} className="mt-2" />
                                    </div>

                                    {/* Status */}
                                    <div>
                                        <InputLabel htmlFor="status" value="Status" />
                                        <select
                                            id="status"
                                            name="status"
                                            value={data.status}
                                            onChange={(e) => setData('status', e.target.value)}
                                            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                        >
                                            <option value="active">Active</option>
                                            <option value="paused">Paused</option>
                                            <option value="cancelled">Cancelled</option>
                                        </select>
                                        <InputError message={errors.status} className="mt-2" />
                                    </div>
                                </div>

                                {/* Notes */}
                                <div>
                                    <InputLabel htmlFor="notes" value="Notes (Optional)" />
                                    <textarea
                                        id="notes"
                                        name="notes"
                                        value={data.notes}
                                        onChange={(e) => setData('notes', e.target.value)}
                                        rows={4}
                                        className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                        placeholder="Any additional notes that will be included in generated invoices..."
                                    />
                                    <InputError message={errors.notes} className="mt-2" />
                                </div>

                                {/* Actions */}
                                <div className="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200">
                                    <Link href={route('recurring-invoices.show', recurringInvoice.id)}>
                                        <SecondaryButton type="button">Cancel</SecondaryButton>
                                    </Link>
                                    <PrimaryButton disabled={processing}>
                                        {processing ? 'Updating...' : 'Update Recurring Invoice'}
                                    </PrimaryButton>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
