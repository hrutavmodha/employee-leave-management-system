<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('End-User Documentation') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <h1 class="text-3xl font-black text-center text-gray-900 mb-6">Documentation</h1>

            <!-- Role Matrix Card -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200 p-6">
                <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <span>🛡️</span> Role-Based Access Control (RBAC)
                </h3>
                <p class="text-sm text-gray-600 mb-4">
                    ELMS uses role-based security permissions. Features and options are customized to your account's role.
                </p>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left font-bold text-gray-500 uppercase tracking-wider">Feature</th>
                                <th class="px-6 py-3 text-center font-bold text-gray-500 uppercase tracking-wider">Employee</th>
                                <th class="px-6 py-3 text-center font-bold text-gray-500 uppercase tracking-wider">Manager</th>
                                <th class="px-6 py-3 text-center font-bold text-gray-500 uppercase tracking-wider">HR/Admin</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            <tr>
                                <td class="px-6 py-4 font-semibold text-gray-800">Personal Dashboard & Profile</td>
                                <td class="px-6 py-4 text-center text-green-600 font-bold">✓</td>
                                <td class="px-6 py-4 text-center text-green-600 font-bold">✓</td>
                                <td class="px-6 py-4 text-center text-green-600 font-bold">✓</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 font-semibold text-gray-800">Apply for & Cancel Leaves</td>
                                <td class="px-6 py-4 text-center text-green-600 font-bold">✓</td>
                                <td class="px-6 py-4 text-center text-green-600 font-bold">✓</td>
                                <td class="px-6 py-4 text-center text-green-600 font-bold">✓</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 font-semibold text-gray-800">Approve / Reject Subordinate Leaves</td>
                                <td class="px-6 py-4 text-center text-red-500 font-bold">✗</td>
                                <td class="px-6 py-4 text-center text-green-600 font-bold">✓</td>
                                <td class="px-6 py-4 text-center text-green-600 font-bold">✓</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 font-semibold text-gray-800">Add / Delete Employees</td>
                                <td class="px-6 py-4 text-center text-red-500 font-bold">✗</td>
                                <td class="px-6 py-4 text-center text-red-500 font-bold">✗</td>
                                <td class="px-6 py-4 text-center text-green-600 font-bold">✓</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 font-semibold text-gray-800">System-wide Leave Reports</td>
                                <td class="px-6 py-4 text-center text-red-500 font-bold">✗</td>
                                <td class="px-6 py-4 text-center text-red-500 font-bold">✗</td>
                                <td class="px-6 py-4 text-center text-green-600 font-bold">✓</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Standard User Features -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Apply for Leave Guide -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200 p-6 space-y-3">
                    <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                        <span>📄</span> How to Apply for Leaves
                    </h3>
                    <p class="text-sm text-gray-600">
                        Employees and managers can request paid leave dynamically through a single dashboard click:
                    </p>
                    <ul class="list-disc list-inside text-xs text-gray-600 space-y-1.5 pl-2">
                        <li>Navigate to the <span class="font-bold">Dashboard</span> page.</li>
                        <li>Click the prominent blue action button <span class="font-bold">Apply for New Leave</span>.</li>
                        <li>Review your <span class="font-bold">My Available Balance</span> cards dynamically displaying remaining active quotas.</li>
                        <li>Complete the fields: leave type, dates, reason, and any medical/official attachments.</li>
                        <li>The system automatically blocks submissions exceeding your current active balance.</li>
                    </ul>
                </div>

                <!-- Profile Customization -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200 p-6 space-y-3">
                    <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                        <span>👤</span> Profile Customization
                    </h3>
                    <p class="text-sm text-gray-600">
                        Customise your personal identity avatar and account safety:
                    </p>
                    <ul class="list-disc list-inside text-xs text-gray-600 space-y-1.5 pl-2">
                        <li>Access your profile from the top-right settings dropdown.</li>
                        <li>Upload a profile picture (supports <span class="font-bold">JPEG, PNG, JPG, GIF</span> up to 2MB).</li>
                        <li>The system will display your picture in the header and mobile navigation drop menus.</li>
                        <li>If no custom picture is set, a fallback badge displays your name's initials.</li>
                        <li>Change passwords securely through the password section.</li>
                    </ul>
                </div>
            </div>

            <!-- Manager and Administrative Controls -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200 p-6 space-y-4">
                <h3 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                    <span>⚙️</span> Manager & Administrator Features
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-2">
                    <div class="space-y-2">
                        <h4 class="text-sm font-bold text-blue-800 uppercase tracking-wider">1. Leave Approvals</h4>
                        <p class="text-xs text-gray-600 leading-relaxed">
                            Managers and HR/Admins can view direct subordinates' pending leave applications in the <span class="font-bold">Pending Approvals</span> tab. Easily approve or reject with a single click to calculate and subtract days immediately.
                        </p>
                    </div>

                    <div class="space-y-2">
                        <h4 class="text-sm font-bold text-blue-800 uppercase tracking-wider">2. Employee Directory</h4>
                        <p class="text-xs text-gray-600 leading-relaxed">
                            Administrators can add new workforce accounts via "+ Add New Employee" under the <span class="font-bold">Employees</span> tab. Lower-level employees can be permanently deleted with a confirmation gate to keep databases updated.
                        </p>
                    </div>

                    <div class="space-y-2">
                        <h4 class="text-sm font-bold text-blue-800 uppercase tracking-wider">3. System Analytics</h4>
                        <p class="text-xs text-gray-600 leading-relaxed">
                            HR/Admins can view statistical widgets in the <span class="font-bold">Reports</span> tab containing Employee summaries, Department metrics (aggregating totals, approvals, and rejections), and historical Monthly absentees.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
