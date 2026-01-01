import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import DangerButton from '@/Components/DangerButton';
import TextInput from '@/Components/TextInput';
import InputLabel from '@/Components/InputLabel';
import RecordPaymentModal from '@/Components/RecordPaymentModal';

const statusColors = {
    draft: 'bg-gray-100 text-gray-800',
    sent: 'bg-blue-100 text-blue-800',
    paid: 'bg-green-100 text-green-800',
    overdue: 'bg-red-100 text-red-800',
};

export default function Show({ invoice }) {
    const [items, setItems] = useState(invoice.items);
    const [payments, setPayments] = useState(invoice.payments || []);
    const [editingItem, setEditingItem] = useState(null);
    const [newItem, setNewItem] = useState({
        description: '',
        quantity: '',
        unit_price: '',
    });
    const [isAddingItem, setIsAddingItem] = useState(false);
    const [showPaymentModal, setShowPaymentModal] = useState(false);

    const handleAddItem = () => {
        if (!newItem.description || !newItem.quantity || !newItem.unit_price) {
            return;
        }

        router.post(
            route('invoices.items.store', invoice.id),
            newItem,
            {
                onSuccess: (page) => {
                    setItems(page.props.invoice.items);
                    setPayments(page.props.invoice.payments || []);
                    setNewItem({ description: '', quantity: '', unit_price: '' });
                    setIsAddingItem(false);
                },
                onError: (errors) => {
                    console.error(errors);
                },
            }
        );
    };

    const handleUpdateItem = (item) => {
        router.put(
            route('invoices.items.update', [invoice.id, item.id]),
            editingItem,
            {
                onSuccess: (page) => {
                    setItems(page.props.invoice.items);
                    setPayments(page.props.invoice.payments || []);
                    setEditingItem(null);
                },
                onError: (errors) => {
                    console.error(errors);
                },
            }
        );
    };

    const handleDeleteItem = (item) => {
        if (confirm('Are you sure you want to delete this item?')) {
            router.delete(
                route('invoices.items.destroy', [invoice.id, item.id]),
                {
                    onSuccess: (page) => {
                        setItems(page.props.invoice.items);
                        setPayments(page.props.invoice.payments || []);
                    },
                    onError: (errors) => {
                        console.error(errors);
                    },
                }
            );
        }
    };

    const startEditingItem = (item) => {
        setEditingItem({
            id: item.id,
            description: item.description,
            quantity: item.quantity,
            unit_price: item.unit_price,
        });
    };

    const cancelEditing = () => {
        setEditingItem(null);
    };

    const cancelAdding = () => {
        setNewItem({ description: '', quantity: '', unit_price: '' });
        setIsAddingItem(false);
    };

    const handleDownloadPdf = () => {
        window.open(route('invoices.download', invoice.id), '_blank');
    };

    const handlePayInvoice = () => {
        router.post(route('invoices.payment.checkout', invoice.id));
    };

    return (
        <AuthenticatedLayout>
            <Head title={`Invoice ${invoice.invoice_number}`} />

            <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 pt-8">
                <div className="flex items-center gap-4 mb-6">
                    <Link href={route('invoices.index')}>
                        <SecondaryButton>← Back to Invoices</SecondaryButton>
                    </Link>
                    <h1 className="text-2xl font-semibold text-gray-900">
                        Invoice {invoice.invoice_number}
                    </h1>
                    <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${statusColors[invoice.status]}`}>
                        {invoice.status}
                    </span>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div className="lg:col-span-2">
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-6 bg-white border-b border-gray-200">
                                <div className="flex justify-between items-center">
                                    <h2 className="text-xl font-semibold text-gray-800">Invoice Items</h2>
                                    {invoice.can_be_modified && (
                                        <PrimaryButton
                                            onClick={() => setIsAddingItem(true)}
                                            disabled={isAddingItem}
                                        >
                                            + Add Item
                                        </PrimaryButton>
                                    )}
                                </div>
                            </div>
                            <div className="p-6">
                                {isAddingItem && (
                                    <div className="border rounded-lg p-4 mb-4 bg-gray-50">
                                        <div className="space-y-4">
                                            <div>
                                                <InputLabel>Description</InputLabel>
                                                <TextInput
                                                    value={newItem.description}
                                                    onChange={(e) =>
                                                        setNewItem({
                                                            ...newItem,
                                                            description: e.target.value,
                                                        })
                                                    }
                                                    placeholder="Item description"
                                                />
                                            </div>
                                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                                <div>
                                                    <InputLabel>Quantity</InputLabel>
                                                    <TextInput
                                                        type="number"
                                                        step="0.01"
                                                        value={newItem.quantity}
                                                        onChange={(e) =>
                                                            setNewItem({
                                                                ...newItem,
                                                                quantity: e.target.value,
                                                            })
                                                        }
                                                        placeholder="1"
                                                    />
                                                </div>
                                                <div>
                                                    <InputLabel>Unit Price</InputLabel>
                                                    <TextInput
                                                        type="number"
                                                        step="0.01"
                                                        value={newItem.unit_price}
                                                        onChange={(e) =>
                                                            setNewItem({
                                                                ...newItem,
                                                                unit_price: e.target.value,
                                                            })
                                                        }
                                                        placeholder="0.00"
                                                    />
                                                </div>
                                            </div>
                                            <div className="flex gap-2">
                                                <PrimaryButton onClick={handleAddItem}>Save</PrimaryButton>
                                                <SecondaryButton onClick={cancelAdding}>Cancel</SecondaryButton>
                                            </div>
                                        </div>
                                    </div>
                                )}

                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-200">
                                        <thead className="bg-gray-50">
                                            <tr>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Description
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Quantity
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Unit Price
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Total
                                                </th>
                                                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Actions
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="bg-white divide-y divide-gray-200">
                                            {items.map((item) => (
                                                <tr key={item.id}>
                                                    <td className="px-6 py-4 text-sm text-gray-900">
                                                        {editingItem && editingItem.id === item.id ? (
                                                            <TextInput
                                                                value={editingItem.description}
                                                                onChange={(e) =>
                                                                    setEditingItem({
                                                                        ...editingItem,
                                                                        description: e.target.value,
                                                                    })
                                                                }
                                                                className="w-full"
                                                            />
                                                        ) : (
                                                            item.description
                                                        )}
                                                    </td>
                                                    <td className="px-6 py-4 text-sm text-gray-900">
                                                        {editingItem && editingItem.id === item.id ? (
                                                            <TextInput
                                                                type="number"
                                                                step="0.01"
                                                                value={editingItem.quantity}
                                                                onChange={(e) =>
                                                                    setEditingItem({
                                                                        ...editingItem,
                                                                        quantity: e.target.value,
                                                                    })
                                                                }
                                                                className="w-24"
                                                            />
                                                        ) : (
                                                            item.quantity
                                                        )}
                                                    </td>
                                                    <td className="px-6 py-4 text-sm text-gray-900">
                                                        {editingItem && editingItem.id === item.id ? (
                                                            <TextInput
                                                                type="number"
                                                                step="0.01"
                                                                value={editingItem.unit_price}
                                                                onChange={(e) =>
                                                                    setEditingItem({
                                                                        ...editingItem,
                                                                        unit_price: e.target.value,
                                                                    })
                                                                }
                                                                className="w-24"
                                                            />
                                                        ) : (
                                                            `$${parseFloat(item.unit_price).toFixed(2)}`
                                                        )}
                                                    </td>
                                                    <td className="px-6 py-4 text-sm text-gray-900">
                                                        ${parseFloat(item.total).toFixed(2)}
                                                    </td>
                                                    <td className="px-6 py-4 text-right text-sm font-medium">
                                                        {invoice.can_be_modified && (
                                                            <div className="flex justify-end gap-2">
                                                                {editingItem && editingItem.id === item.id ? (
                                                                    <>
                                                                        <PrimaryButton onClick={() => handleUpdateItem(item)}>
                                                                            Save
                                                                        </PrimaryButton>
                                                                        <SecondaryButton onClick={cancelEditing}>
                                                                            Cancel
                                                                        </SecondaryButton>
                                                                    </>
                                                                ) : (
                                                                    <>
                                                                        <SecondaryButton onClick={() => startEditingItem(item)}>
                                                                            Edit
                                                                        </SecondaryButton>
                                                                        <DangerButton onClick={() => handleDeleteItem(item)}>
                                                                            Delete
                                                                        </DangerButton>
                                                                    </>
                                                                )}
                                                            </div>
                                                        )}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>

                                {items.length === 0 && !isAddingItem && (
                                    <div className="text-center py-8">
                                        <p className="text-gray-500 mb-4">No items yet</p>
                                        {invoice.can_be_modified && (
                                            <PrimaryButton onClick={() => setIsAddingItem(true)}>
                                                Add First Item
                                            </PrimaryButton>
                                        )}
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>

                    <div>
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-6 bg-white border-b border-gray-200">
                                <h2 className="text-xl font-semibold text-gray-800">Invoice Details</h2>
                            </div>
                            <div className="p-6 space-y-4">
                                <div>
                                    <InputLabel className="text-sm font-medium text-gray-500">Client</InputLabel>
                                    <p className="font-medium">{invoice.client.name}</p>
                                    {invoice.client.email && (
                                        <p className="text-sm text-gray-600">{invoice.client.email}</p>
                                    )}
                                </div>

                                <div>
                                    <InputLabel className="text-sm font-medium text-gray-500">Issue Date</InputLabel>
                                    <p className="font-medium">{new Date(invoice.issue_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</p>
                                </div>

                                <div>
                                    <InputLabel className="text-sm font-medium text-gray-500">Due Date</InputLabel>
                                    <p className="font-medium">{new Date(invoice.due_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</p>
                                </div>

                                <hr />

                                <div className="space-y-2">
                                    <div className="flex justify-between">
                                        <span>Subtotal:</span>
                                        <span>{invoice.formatAmount ? invoice.formatAmount(parseFloat(invoice.subtotal)) : '$' + parseFloat(invoice.subtotal).toFixed(2)}</span>
                                    </div>
                                    {(!invoice.wasTaxExemptAtTime && invoice.tax > 0) && (
                                        <div className="flex justify-between">
                                            <span>Tax ({invoice.getFormattedTaxRateAtTime}):</span>
                                            <span>{invoice.formatAmount ? invoice.formatAmount(parseFloat(invoice.tax)) : '$' + parseFloat(invoice.tax).toFixed(2)}</span>
                                        </div>
                                    )}
                                    <div className="flex justify-between">
                                        <span>Discount:</span>
                                        <span>{invoice.formatAmount ? invoice.formatAmount(parseFloat(invoice.discount || 0)) : '$' + parseFloat(invoice.discount || 0).toFixed(2)}</span>
                                    </div>
                                    <div className="flex justify-between font-bold text-lg border-t pt-2">
                                        <span>Total:</span>
                                        <span>{invoice.formatAmount ? invoice.formatAmount(parseFloat(invoice.total)) : '$' + parseFloat(invoice.total).toFixed(2)}</span>
                                    </div>
                                    {(invoice.total_paid > 0 || invoice.payments?.length > 0) && (
                                        <>
                                            <hr />
                                            <div className="flex justify-between text-green-600">
                                                <span>Total Paid:</span>
                                                <span>{invoice.getFormattedTotalPaid ? invoice.getFormattedTotalPaid() : '$' + parseFloat(invoice.total_paid || 0).toFixed(2)}</span>
                                            </div>
                                            <div className="flex justify-between font-bold text-lg">
                                                <span>Remaining Balance:</span>
                                                <span>{invoice.getFormattedRemainingBalance ? invoice.getFormattedRemainingBalance() : '$' + parseFloat(invoice.total - (invoice.total_paid || 0)).toFixed(2)}</span>
                                            </div>
                                        </>
                                    )}
                                </div>
                                {invoice.notes && (
                                    <>
                                        <hr />
                                        <div>
                                            <InputLabel className="text-sm font-medium text-gray-500">Notes</InputLabel>
                                            <p className="text-gray-700 whitespace-pre-wrap">{invoice.notes}</p>
                                        </div>
                                    </>
                                )}

                                <hr />

                                <div className="flex flex-wrap justify-end gap-2 mt-4">
                                    <SecondaryButton
                                        onClick={handleDownloadPdf}
                                        disabled={false}
                                    >
                                        Download PDF
                                    </SecondaryButton>
                                    <Link href={route('invoices.edit', invoice.id)}>
                                        <PrimaryButton
                                            disabled={!invoice.can_be_modified}
                                        >
                                            Edit Invoice
                                        </PrimaryButton>
                                    </Link>
                                    {invoice.status === 'sent' && (
                                        <PrimaryButton
                                            onClick={handlePayInvoice}
                                            className="bg-green-600 hover:bg-green-700"
                                        >
                                            Pay Invoice
                                        </PrimaryButton>
                                    )}
                                    {invoice.status !== 'paid' && (
                                        <PrimaryButton
                                            onClick={() => setShowPaymentModal(true)}
                                        >
                                            Record Payment
                                        </PrimaryButton>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>

                    {payments.length > 0 && (
                        <div className="lg:col-span-3">
                            <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                                <div className="p-6 bg-white border-b border-gray-200">
                                    <h2 className="text-xl font-semibold text-gray-800">Payment History</h2>
                                </div>
                                <div className="p-6">
                                    <div className="space-y-3">
                                        {payments.map((payment) => (
                                            <div key={payment.id} className="border rounded-lg p-4">
                                                <div className="flex justify-between items-start">
                                                    <div>
                                                        <div className="font-medium text-lg">
                                                            ${parseFloat(payment.amount).toFixed(2)}
                                                        </div>
                                                        <div className="text-sm text-gray-600">
                                                            {payment.payment_method_label} • {new Date(payment.payment_date).toLocaleDateString()}
                                                        </div>
                                                        {payment.notes && (
                                                            <div className="text-sm text-gray-500 mt-1">
                                                                {payment.notes}
                                                            </div>
                                                        )}
                                                    </div>
                                                    <div className="text-sm text-gray-500">
                                                        {new Date(payment.created_at).toLocaleDateString()}
                                                    </div>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}
                </div>

                <RecordPaymentModal
                    invoice={invoice}
                    show={showPaymentModal}
                    onClose={() => setShowPaymentModal(false)}
                />
            </div>
        </AuthenticatedLayout>
    );
}
