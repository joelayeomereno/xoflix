<?php if (!defined('ABSPATH')) { exit; } ?>
        // --- 1. ERROR BOUNDARY ---
        class ErrorBoundary extends Component {
            constructor(props) { super(props); this.state = { hasError: false, error: null }; }
            static getDerivedStateFromError(error) { return { hasError: true, error }; }
            render() {
                if (this.state.hasError) {
                    return el('div', {className: 'flex flex-col items-center justify-center h-full p-10 text-center'}, 
                        el('div', {className: 'w-16 h-16 bg-rose-100 text-rose-500 rounded-full flex items-center justify-center mb-4 text-2xl font-bold'}, '!'),
                        el('h2', {className:'text-xl font-bold text-slate-900'}, 'App Crashed'),
                        el('p', {className:'text-sm text-slate-500 mt-2 mb-6'}, this.state.error.toString()),
                        el('button', {onClick:()=>window.location.reload(), className:'px-6 py-3 bg-slate-900 text-white rounded-xl font-bold'}, 'Reload')
                    );
                }
                return this.props.children;
            }
        }

