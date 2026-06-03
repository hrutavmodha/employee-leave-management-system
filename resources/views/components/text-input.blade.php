@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:text-gray-300 dark:focus:border-indigo-600 dark:focus:ring-indigo-600 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm']) }}>
