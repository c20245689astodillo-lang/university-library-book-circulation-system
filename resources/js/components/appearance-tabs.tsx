import { Appearance, useAppearance } from '@/hooks/use-appearance';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import { LucideIcon, Monitor, Moon, Sun } from 'lucide-react';
import { HTMLAttributes } from 'react';

export default function AppearanceToggleTab({ className = '', ...props }: HTMLAttributes<HTMLDivElement>) {
    const { appearance, updateAppearance } = useAppearance();

    const tabs: { value: Appearance; icon: LucideIcon; label: string }[] = [
        { value: 'light', icon: Sun, label: 'Light' },
        { value: 'dark', icon: Moon, label: 'Dark' },
        { value: 'system', icon: Monitor, label: 'System' },
    ];

    return (
        <div className={cn('inline-flex gap-1 rounded-lg bg-neutral-100 p-1 dark:bg-neutral-800', className)} {...props}>
            {tabs.map(({ value, icon: Icon, label }) => (
                <Button
                    key={value}
                    type="button"
                    onClick={() => updateAppearance(value)}
                    variant={appearance === value ? 'secondary' : 'ghost'}
                    size="sm"
                    className={cn('px-3.5', appearance === value && 'shadow-xs')}
                >
                    <Icon className="h-4 w-4" />
                    <span>{label}</span>
                </Button>
            ))}
        </div>
    );
}
