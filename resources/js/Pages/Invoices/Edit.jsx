import React, { useState, useEffect, useCallback } from 'react';
import { Head, Link, useForm, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import DangerButton from '@/Components/DangerButton';
import TextInput from '@/Components/TextInput';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';

export default function Edit({ invoice, clients }) {
    const { data, setData, put, processing, errors, reset } = useForm({
        client_id: invoice.client_id.toString(),
        number: invoice.number,
        status: invoice.status,
        issued_at: invoice.issued_at,
        due_at: invoice.due_at,
        items: invoice.items.map(item => ({
            description: item.description,
            quantity: item.quantity.toString(),
            unit_price: item.unit_price.toString(),
        })),
    });

    const [subtotal, setSubtotal] = useState(invoice.subtotal);
    const [tax, setTax] = useState(invoice.tax);
    const [total, setTotal] = useState(invoice.total);

    const calculateTotals = useCallback(() => {
        const subtotalAmount = data.items.reduce((sum, item) => {
            const quantity = parseFloat(item.quantity) || 0;
            const unitPrice = parseFloat(item.unit_price) || 0;
            return sum + (quantity * unitPrice);
        }, 0);

        const taxAmount = subtotalAmount * 0.1; // 10% tax
        const totalAmount = subtotalAmount + taxAmount;

        setSubtotal(subtotalAmount);
        setTax(taxAmount);
        setTotal(totalAmount);
    }, [data.items]);

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
        const newItems = data.items.filter((_, i) => i !== index);
        setData('items', newItems);
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
        const totalAmount = subtotalAmount + taxAmount;
        
        setData('subtotal', subtotalAmount);
        setData('tax', taxAmount);
        setData('total', totalAmount);
        
        put(route('invoices.update', invoice.id));
    };

    return (
        <AuthenticatedLayout>
            <Head title={`Edit Invoice ${invoice.number}`} />

            <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div className="flex items-center gap-4 mb-6">
                    <Link href={route('invoices.index')}>
                        <SecondaryButton>← Back to Invoices</SecondaryButton>
                    </Link>
                    <h1 className="text-2xl font-semibold text-gray-900">
                        Edit Invoice {invoice.number}
                    </h1>
                </div>

                {!invoice.can_be_modified && (
                    <div className="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <p className="text-yellow-800">
                            This invoice cannot be modified because it's already paid.
                        </p>
                    </div>
                )}

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
                                                disabled={!invoice.can_be_modified}
                                                className={`mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm ${errors.client_id ? 'border-red-500' : ''} ${!invoice.can_be_modified ? 'bg-gray-100' : ''}`}
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
                                            <InputLabel htmlFor="number">Invoice Number</InputLabel>
                                            <TextInput
                                                id="number"
                                                value={data.number}
                                                onChange={(e) => setData('number', e.target.value)}
                                                disabled={!invoice.can_be_modified}
                                                className={errors.number ? 'border-red-500' : ''}
                                            />
                                            <InputError message={errors.number} className="mt-2" />
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-2 gap-4">
                                        <div>
                                            <InputLabel htmlFor="issued_at">Issue Date</InputLabel>
                                            <TextInput
                                                id="issued_at"
                                                type="date"
                                                value={data.issued_at}
                                                onChange={(e) => setData('issued_at', e.target.value)}
                                                disabled={!invoice.can_be_modified}
                                                className={errors.issued_at ? 'border-red-500' : ''}
                                            />
                                            <InputError message={errors.issued_at} className="mt-2" />
                                        </div>

                                        <div>
                                            <InputLabel htmlFor="due_at">Due Date</InputLabel>
                                            <TextInput
                                                id="due_at"
                                                type="date"
                                                value={data.due_at}
                                                onChange={(e) => setData('due_at', e.target.value)}
                                                disabled={!invoice.can_be_modified}
                                                className={errors.due_at ? 'border-red-500' : ''}
                                            />
                                            <InputError message={errors.due_at} className="mt-2" />
                                        </div>
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
                            </div>

                            <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                                <div className="p-6 bg-white border-b border-gray-200">
                                    <div className="flex justify-between items-center">
                                        <h2 className="text-xl font-semibold text-gray-800">Invoice Items</h2>
                                        <PrimaryButton 
                                            type="button" 
                                            onClick={addItem}
                                            disabled={!invoice.can_be_modified}
                                        >
                                            + Add Item
                                        </PrimaryButton>
                                    </div>
                                </div>
                                <div className="p-6">
                                    <InputError message={errors.items} className="mb-4" />
                                    
                                    <div className="space-y-4">
                                        {data.items.map((item, index) => (
                                            <div key={index} className="border rounded-lg p-4">
                                                <div className="grid grid-cols-12 gap-2 items-end">
                                                    <div className="col-span-6">
                                                        <InputLabel>Description</InputLabel>
                                                        <TextInput
                                                            value={item.description}
                                                            onChange={(e) => updateItem(index, 'description', e.target.value)}
                                                            placeholder="Item description"
                                                            disabled={!invoice.can_be_modified}
                                                        />
                                                        <InputError message={errors[`items.${index}.description`]} className="mt-2" />
                                                    </div>
                                                    <div className="col-span-2">
                                                        <InputLabel>Quantity</InputLabel>
                                                        <TextInput
                                                            type="number"
                                                            step="0.01"
                                                            value={item.quantity}
                                                            onChange={(e) => updateItem(index, 'quantity', e.target.value)}
                                                            placeholder="1"
                                                            disabled={!invoice.can_be_modified}
                                                        />
                                                        <InputError message={errors[`items.${index}.quantity`]} className="mt-2" />
                                                    </div>
                                                    <div className="col-span-2">
                                                        <InputLabel>Unit Price</InputLabel>
                                                        <TextInput
                                                            type="number"
                                                            step="0.01"
                                                            value={item.unit_price}
                                                            onChange={(e) => updateItem(index, 'unit_price', e.target.value)}
                                                            placeholder="0.00"
                                                            disabled={!invoice.can_be_modified}
                                                        />
                                                        <InputError message={errors[`items.${index}.unit_price`]} className="mt-2" />
                                                    </div>
                                                    <div className="col-span-2">
                                                        <DangerButton
                                                            type="button"
                                                            onClick={() => removeItem(index)}
                                                            disabled={data.items.length === 1 || !invoice.can_be_modified}
                                                        >
                                                            Delete
                                                        </DangerButton>
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
                                            <span>${parseFloat(subtotal).toFixed(2)}</span>
                                        </div>
                                        <div className="flex justify-between">
                                            <span>Tax (10%):</span>
                                            <span>${parseFloat(tax).toFixed(2)}</span>
                                        </div>
                                        <div className="flex justify-between font-bold text-lg border-t pt-2">
                                            <span>Total:</span>
                                            <span>${parseFloat(total).toFixed(2)}</span>
                                        </div>
                                    </div>

                                    <hr />

                                    <div className="flex gap-2">
                                        <PrimaryButton 
                                            type="submit" 
                                            disabled={processing || !invoice.can_be_modified} 
                                            className="flex-1"
                                        >
                                            Update Invoice
                                        </PrimaryButton>
                                        <Link href={route('invoices.show', invoice.id)}>
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
