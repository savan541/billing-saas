import React, { useState, useEffect, useCallback } from 'react';
import { Head, Link, useForm, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import DangerButton from '@/Components/DangerButton';
import TextInput from '@/Components/TextInput';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';

export default function Create({ clients }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        client_id: '',
        status: 'draft',
        issue_date: new Date().toISOString().split('T')[0],
        due_date: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
        notes: '',
        items: [
            {
                description: '',
                quantity: '1',
                unit_price: '0',
            },
        ],
    });

    const [subtotal, setSubtotal] = useState(0);
    const [tax, setTax] = useState(0);
    const [total, setTotal] = useState(0);
    const [currencySymbol, setCurrencySymbol] = useState('$');

    const calculateTotals = useCallback(() => {
        const subtotalAmount = data.items.reduce((sum, item) => {
            const quantity = parseFloat(item.quantity) || 0;
            const unitPrice = parseFloat(item.unit_price) || 0;
            return sum + (quantity * unitPrice);
        }, 0);

        // Get client tax rate and currency
        const selectedClient = clients.find(c => c.id === parseInt(data.client_id));
        console.log('Selected client:', selectedClient);
        const taxRate = selectedClient && !selectedClient.tax_exempt ? (selectedClient.tax_rate || 0) : 0;
        const currencySymbol = selectedClient?.currency_symbol || '$';
        console.log('Currency symbol:', currencySymbol);
        const taxAmount = subtotalAmount * taxRate;
        const totalAmount = subtotalAmount + taxAmount;

        setSubtotal(subtotalAmount);
        setTax(taxAmount);
        setTotal(totalAmount);
        setCurrencySymbol(currencySymbol);
    }, [data.items, data.client_id, clients]);

    useEffect(() => {
        calculateTotals();
    }, [calculateTotals]);

    const addItem = () => {
        setData('items', [
            ...data.items,
            {
                description: '',
                quantity: '1',
                unit_price: '0',
            },
        ]);
    };

    const removeItem = (index) => {
        if (data.items.length > 1) {
            const newItems = data.items.filter((_, i) => i !== index);
            setData('items', newItems);
        }
    };

    const updateItem = (index, field, value) => {
        const newItems = [...data.items];
        newItems[index][field] = value;
        setData('items', newItems);
    };

    const submit = (e) => {
        e.preventDefault();
        
        // Calculate totals and set them in form data before submission
        const subtotalAmount = data.items.reduce((sum, item) => {
            const quantity = parseFloat(item.quantity) || 0;
            const unitPrice = parseFloat(item.unit_price) || 0;
            return sum + (quantity * unitPrice);
        }, 0);
        
        const taxAmount = subtotalAmount * 0.1;
        const discountAmount = 0; // Can be made configurable later
        const totalAmount = subtotalAmount + taxAmount - discountAmount;
        
        post(route('invoices.store'), {
            ...data,
            onSuccess: () => reset(),
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Create Invoice" />

            <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 pt-8">
                <div className="flex items-center gap-4 mb-6">
                    <Link href={route('invoices.index')}>
                        <SecondaryButton>← Back to Invoices</SecondaryButton>
                    </Link>
                    <h1 className="text-2xl font-semibold text-gray-900">Create Invoice</h1>
                </div>

                <form onSubmit={submit}>
                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <div className="lg:col-span-2 space-y-6">
                            <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                                <div className="p-6 bg-white border-b border-gray-200">
                                    <h2 className="text-xl font-semibold text-gray-800">Invoice Details</h2>
                                </div>
                                <div className="p-6 space-y-4">
                                    <div className="grid grid-cols-2 gap-4">
                                        <div>
                                            <InputLabel htmlFor="client_id">Client</InputLabel>
                                            <select
                                                id="client_id"
                                                value={data.client_id}
                                                onChange={(e) => setData('client_id', e.target.value)}
                                                className={`mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm ${errors.client_id ? 'border-red-500' : ''}`}
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

                                        <div>
                                            <InputLabel htmlFor="status">Status</InputLabel>
                                            <select
                                                id="status"
                                                value={data.status}
                                                onChange={(e) => setData('status', e.target.value)}
                                                className="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                            >
                                                <option value="draft">Draft</option>
                                                <option value="sent">Sent</option>
                                                <option value="paid">Paid</option>
                                                <option value="overdue">Overdue</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-2 gap-4">
                                        <div>
                                            <InputLabel htmlFor="issue_date">Issue Date</InputLabel>
                                            <TextInput
                                                id="issue_date"
                                                type="date"
                                                value={data.issue_date}
                                                onChange={(e) => setData('issue_date', e.target.value)}
                                                className={errors.issue_date ? 'border-red-500' : ''}
                                            />
                                            <InputError message={errors.issue_date} className="mt-2" />
                                        </div>

                                        <div>
                                            <InputLabel htmlFor="due_date">Due Date</InputLabel>
                                            <TextInput
                                                id="due_date"
                                                type="date"
                                                value={data.due_date}
                                                onChange={(e) => setData('due_date', e.target.value)}
                                                className={errors.due_date ? 'border-red-500' : ''}
                                            />
                                            <InputError message={errors.due_date} className="mt-2" />
                                        </div>
                                    </div>

                                    <div>
                                        <InputLabel htmlFor="notes">Notes</InputLabel>
                                        <textarea
                                            id="notes"
                                            value={data.notes}
                                            onChange={(e) => setData('notes', e.target.value)}
                                            rows={3}
                                            className="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                            placeholder="Optional notes for this invoice"
                                        />
                                        <InputError message={errors.notes} className="mt-2" />
                                    </div>
                                </div>
                            </div>

                            <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                                <div className="p-6 bg-white border-b border-gray-200">
                                    <div className="flex justify-between items-center">
                                        <h2 className="text-xl font-semibold text-gray-800">Invoice Items</h2>
                                        <PrimaryButton type="button" onClick={addItem}>
                                            + Add Item
                                        </PrimaryButton>
                                    </div>
                                </div>
                                <div className="p-6">
                                    <InputError message={errors.items} className="mb-4" />
                                    
                                    <div className="space-y-4">
                                        {data.items.map((item, index) => (
                                            <div key={index} className="border rounded-lg p-4">
                                                <div className="grid grid-cols-12 gap-6 items-end">
                                                    <div className="col-span-5">
                                                        <InputLabel>Description</InputLabel>
                                                        <TextInput
                                                            value={item.description}
                                                            onChange={(e) => updateItem(index, 'description', e.target.value)}
                                                            placeholder="Item description"
                                                        />
                                                        <InputError message={errors[`items.${index}.description`]} className="mt-2" />
                                                    </div>
                                                    <div className="col-span-3">
                                                        <InputLabel>Quantity</InputLabel>
                                                        <TextInput
                                                            type="number"
                                                            step="0.01"
                                                            value={item.quantity}
                                                            onChange={(e) => updateItem(index, 'quantity', e.target.value)}
                                                            placeholder="1"
                                                        />
                                                        <InputError message={errors[`items.${index}.quantity`]} className="mt-2" />
                                                    </div>
                                                    <div className="col-span-3">
                                                        <InputLabel>Unit Price</InputLabel>
                                                        <TextInput
                                                            type="number"
                                                            step="0.01"
                                                            value={item.unit_price}
                                                            onChange={(e) => updateItem(index, 'unit_price', e.target.value)}
                                                            placeholder="0.00"
                                                        />
                                                        <InputError message={errors[`items.${index}.unit_price`]} className="mt-2" />
                                                    </div>
                                                    <div className="col-span-1 flex items-end">
                                                        <button
                                                            type="button"
                                                            onClick={() => removeItem(index)}
                                                            disabled={data.items.length === 1}
                                                            className="w-10 h-10 flex items-center justify-center rounded-lg border border-red-300 bg-red-50 text-red-600 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200"
                                                            title="Remove item"
                                                        >
                                                            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                                <div className="p-6 bg-white border-b border-gray-200">
                                    <h2 className="text-xl font-semibold text-gray-800">Summary</h2>
                                </div>
                                <div className="p-6 space-y-4">
                                    <div className="space-y-2">
                                        <div className="flex justify-between">
                                            <span>Subtotal:</span>
                                            <span>{currencySymbol}{parseFloat(subtotal).toFixed(2)}</span>
                                        </div>
                                        {(() => {
                                            const selectedClient = clients.find(c => c.id === parseInt(data.client_id));
                                            const taxRate = selectedClient && !selectedClient.tax_exempt ? (selectedClient.tax_rate || 0) : 0;
                                            return taxRate > 0 ? (
                                                <div className="flex justify-between">
                                                    <span>Tax ({(taxRate * 100).toFixed(2)}%):</span>
                                                    <span>{currencySymbol}{parseFloat(tax).toFixed(2)}</span>
                                                </div>
                                            ) : null;
                                        })()}
                                        <div className="flex justify-between">
                                            <span>Discount:</span>
                                            <span>{currencySymbol}0.00</span>
                                        </div>
                                        <div className="flex justify-between font-bold text-lg border-t pt-2">
                                            <span>Total:</span>
                                            <span>{currencySymbol}{parseFloat(total).toFixed(2)}</span>
                                        </div>
                                    </div>

                                    <hr />

                                    <div className="flex gap-2">
                                        <PrimaryButton type="submit" disabled={processing} className="flex-1">
                                            Create Invoice
                                        </PrimaryButton>
                                        <Link href={route('invoices.index')}>
                                            <SecondaryButton type="button">
                                                Cancel
                                            </SecondaryButton>
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
