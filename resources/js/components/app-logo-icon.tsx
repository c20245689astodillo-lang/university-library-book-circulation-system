import type { ImgHTMLAttributes } from 'react';

export default function AppLogoIcon(props: ImgHTMLAttributes<HTMLImageElement>) {
    return (
        <picture>
            <source srcSet="/library-logo.avif" type="image/avif" />
            <source srcSet="/library-logo.webp" type="image/webp" />
            <img src="/library-logo.png" alt="University Library BCS" {...props} />
        </picture>
    );
}
