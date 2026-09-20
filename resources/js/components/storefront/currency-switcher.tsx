import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { type SharedData } from '@/types';
import { router, usePage } from '@inertiajs/react';
import { Check, ChevronDown } from 'lucide-react';

export function CurrencySwitcher({ compact = false }: { compact?: boolean }) {
    const { currency } = usePage<SharedData>().props;
    const current = currency.options.find((option) => option.code === currency.current) ?? currency.options[0];

    if (!current || currency.options.length < 2) {
        return null;
    }

    const switchTo = (code: string) => {
        if (code === currency.current) {
            return;
        }

        router.post(route('currency.update'), { currency: code }, { preserveScroll: true });
    };

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="ghost"
                    size={compact ? 'sm' : 'default'}
                    className={compact ? 'h-9 gap-1 px-2 text-xs font-semibold' : 'justify-between gap-2'}
                    aria-label={`Currency, ${current.code}`}
                >
                    <span>{current.code}</span>
                    <ChevronDown className="size-3.5 opacity-70" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="max-h-80 w-56 overflow-y-auto">
                {currency.options.map((option) => (
                    <DropdownMenuItem key={option.code} onSelect={() => switchTo(option.code)}>
                        <span className="font-medium">{option.code}</span>
                        <span className="text-muted-foreground truncate">{option.name}</span>
                        {option.code === currency.current && <Check className="ml-auto size-4" />}
                    </DropdownMenuItem>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
