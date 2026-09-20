export const defaultCatalogSort = 'custom';

export const catalogSortOptions = [
    { value: 'custom', label: 'Custom' },
    { value: 'newest', label: 'Newest' },
    { value: 'oldest', label: 'Oldest' },
    { value: 'price_asc', label: 'Price: low to high' },
    { value: 'price_desc', label: 'Price: high to low' },
    { value: 'title_asc', label: 'Name: A to Z' },
    { value: 'title_desc', label: 'Name: Z to A' },
] as const;
