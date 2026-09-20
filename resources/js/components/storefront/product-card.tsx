import { ProductCard as PickleCard } from '@/components/yumyum';
import { toPickle } from '@/lib/pickle';
import { type ProductSummary } from '@/types';

export function ProductCard({ product }: { product: ProductSummary }) {
    return <PickleCard product={toPickle(product)} />;
}
