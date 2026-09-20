import { images } from '@/lib/yumyum-images';
import { type SharedData } from '@/types';
import { usePage } from '@inertiajs/react';

interface StoreLogoProps {
    className?: string;
    imageClassName?: string;
    fallbackClassName?: string;
}

export function StoreLogo({ className, imageClassName, fallbackClassName }: StoreLogoProps) {
    const { store } = usePage<SharedData>().props;

    if (store.logo_url) {
        return <img src={store.logo_url} alt={store.name} className={imageClassName ?? className} />;
    }

    return <img src={images.logoImage} alt={store.name || 'YumYum Pickles'} className={imageClassName ?? className} />;
}
