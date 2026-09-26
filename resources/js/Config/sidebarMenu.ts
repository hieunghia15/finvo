export interface MenuItem {
    id: string;
    title: string;
    icon?: string;
    url?: string;
    subItems?: MenuItem[];
    method?: 'get' | 'post' | 'put' | 'patch' | 'delete';
}

export interface MenuGroup {
    id: string;
    title: string;
    items: MenuItem[];
}

export const sidebarMenuConfig: MenuGroup[] = [
    {
        id: 'main',
        title: 'MAIN',
        items: [
            {
                id: 'frontPages',
                title: 'Front Pages',
                icon: 'note_stack',
                subItems: [
                    { id: 'home', title: 'Home', url: '/' },
                    { id: 'features', title: 'Features', url: '/features' },
                    { id: 'ourTeam', title: 'Our Team', url: '/our-team' },
                    { id: 'faqs', title: 'FAQ’s', url: '/faqs' },
                    { id: 'contact', title: 'Contact', url: '/contact' },
                ],
            },
            {
                id: 'googleMap',
                title: 'Google Map',
                icon: 'map',
                url: '/google-map',
            },
            {
                id: 'categories',
                title: 'Categories',
                icon: 'category',
                url: '/categories',
            },
        ],
    },
    {
        id: 'others',
        title: 'OTHERS',
        items: [
            {
                id: 'account',
                title: 'Account',
                icon: 'account_circle',
                url: '/account',
            },
            // {
            //     id: 'multiLevel',
            //     title: 'Multi Level Menu',
            //     icon: 'unfold_more',
            //     subItems: [
            //         {
            //             id: 'levelOne',
            //             title: 'Level One',
            //             subItems: [
            //                 {
            //                     id: 'levelThree',
            //                     title: 'Level Three',
            //                     url: '#',
            //                 },
            //             ],
            //         },
            //     ],
            // }
        ],
    },
];

export default sidebarMenuConfig;
