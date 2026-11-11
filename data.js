/**
 * Kinz Jewellery Admin - Comprehensive Data Store & Guarantee Jewellery Dataset
 * Gold Plated, Stainless Steel Plated & Anti-Tarnish Jewellery Store Management
 */

const STORAGE_KEY = 'kinz_guarantee_jewellery_inr_v8';

const defaultJewelleryData = {
  // Indian Store Settings
  settings: {
    currency: 'INR',
    currencySymbol: '₹',
    storeName: 'Kinz Guarantee Jewellery Store',
    taxRate: 5.0
  },

  // Store Assurance & Guarantee Standards
  assurance: {
    policy: '1-Year Color & Polish Guarantee',
    platingTech: '24K Micro Gold Plating (3-5 Micron)',
    certification: 'Anti-Tarnish & Skin Friendly Certified'
  },

  // KPI Statistics (INR)
  stats: {
    totalSales: 3269990,
    todaysSales: 48500,
    totalOrders: 1420,
    pendingOrders: 18,
    completedOrders: 1364,
    cancelledOrders: 38,
    totalCustomers: 2840,
    lowStockThreshold: 5
  },

  // Exactly the 8 User-Requested Categories
  categories: [
    {
      id: 'cat-rings',
      name: 'Rings',
      slug: 'rings',
      image: 'assets/ring.jpg',
      productCount: 4,
      seoTitle: 'Gold Plated & Stainless Steel Rings | Kinz Jewellery',
      seoDescription: 'Anti-tarnish, waterproof gold plated and stainless steel plated rings for daily wear and party wear.',
      status: 'Active'
    },
    {
      id: 'cat-necklaces',
      name: 'Necklaces',
      slug: 'necklaces',
      image: 'https://images.unsplash.com/photo-1599643478518-a784e5dc4c8f?auto=format&fit=crop&w=600&q=80',
      productCount: 3,
      seoTitle: 'Gold Plated & Stainless Steel Necklaces | Kinz Jewellery',
      seoDescription: 'Festive chokers and daily wear necklaces with a long-lasting anti-tarnish gold plated finish.',
      status: 'Active'
    },
    {
      id: 'cat-earrings',
      name: 'Earrings',
      slug: 'earrings',
      image: 'https://images.unsplash.com/photo-1630019852942-f89202989a59?auto=format&fit=crop&w=600&q=80',
      productCount: 2,
      seoTitle: 'Jhumkas & Stud Earrings | Kinz Jewellery',
      seoDescription: 'Lightweight, skin-friendly anti-tarnish earrings for festive occasions and daily wear.',
      status: 'Active'
    },
    {
      id: 'cat-bangles',
      name: 'Bangles',
      slug: 'bangles',
      image: 'https://images.unsplash.com/photo-1611591475879-1662a9332e1d?auto=format&fit=crop&w=600&q=80',
      productCount: 2,
      seoTitle: 'Gold Plated Bangles & Kadas | Kinz Jewellery',
      seoDescription: 'Broad festive kadas and daily wear bangle sets in a waterproof, anti-tarnish gold plated finish.',
      status: 'Active'
    },
    {
      id: 'cat-bracelets',
      name: 'Bracelets',
      slug: 'bracelets',
      image: 'https://images.unsplash.com/photo-1598560917505-59a3ad559071?auto=format&fit=crop&w=600&q=80',
      productCount: 1,
      seoTitle: 'Anti-Tarnish Bracelets | Kinz Jewellery',
      seoDescription: 'Modern charm bracelets, crystal tennis bracelets and cuff designs for daily wear and party wear.',
      status: 'Active'
    },
    {
      id: 'cat-chains',
      name: 'Chains',
      slug: 'chains',
      image: 'https://images.unsplash.com/photo-1601121141461-9d6647bca1ed?auto=format&fit=crop&w=600&q=80',
      productCount: 1,
      seoTitle: 'Daily Wear Gold Plated Chains | Kinz Jewellery',
      seoDescription: 'Rope, box, wheat and figaro pattern waterproof, anti-tarnish chains for men and women.',
      status: 'Active'
    },
    {
      id: 'cat-anklets',
      name: 'Anklets',
      slug: 'anklets',
      image: 'https://images.unsplash.com/photo-1535632066927-ab7c9ab60908?auto=format&fit=crop&w=600&q=80',
      productCount: 1,
      seoTitle: 'Traditional & Sleek Anklets (Payal) | Kinz Jewellery',
      seoDescription: 'Ghungroo festive payals and sleek anti-tarnish everyday payal anklets.',
      status: 'Active'
    },
    {
      id: 'cat-kids',
      name: 'Kids Acccessories',
      slug: 'kids-accessories',
      image: 'https://images.unsplash.com/photo-1579783902614-a3fb3927b675?auto=format&fit=crop&w=600&q=80',
      productCount: 1,
      seoTitle: 'Skin-Friendly Kids Jewellery & Accessories | Kinz Jewellery',
      seoDescription: 'Adjustable nazariya bangles, enamel floral earrings and lightweight chains for kids.',
      status: 'Active'
    }
  ],

  // Guarantee Jewellery Products (Interactive uploaded pieces)
  products: [
    {
      id: 'PROD-101',
      title: 'Aura Crown Solitaire Guarantee Ring',
      sku: 'KJ-RNG-001',
      category: 'Rings',
      finish: 'Micro Gold Plated',
      guarantee: '1 Year Guarantee',
      stoneType: 'AAA Swiss Cubic Zirconia',
      weightGrams: 4.2,
      basePrice: 1499,
      makingCharges: 0,
      discountPct: 10,
      stock: 18,
      reorderLevel: 5,
      status: 'Active',
      featured: true,
      images: [
        'assets/ring.jpg',
        'assets/ring_macro.jpg',
        'assets/ring_box.jpg',
        'assets/ring_hand.jpg'
      ],
      salesCount: 142,
      revenueGenerated: 212858,
      description: 'Elegant round cut AAA zirconia set on high-polish micro gold band with anti-tarnish protective coating.'
    },
    {
      id: 'PROD-109',
      title: 'Imperial Emerald-Cut Micro Gold Solitaire Ring',
      sku: 'KJ-RNG-002',
      category: 'Rings',
      finish: '24K Micron Gold Plated',
      guarantee: '1 Year Guarantee',
      stoneType: 'Faceted Emerald Green Stone with CZ Halo',
      weightGrams: 5.1,
      basePrice: 1899,
      makingCharges: 0,
      discountPct: 0,
      stock: 14,
      reorderLevel: 4,
      status: 'Active',
      featured: true,
      images: [
        'assets/ring_macro.jpg',
        'assets/ring_box.jpg',
        'assets/ring.jpg',
        'assets/ring_hand.jpg'
      ],
      salesCount: 96,
      revenueGenerated: 182304,
      description: 'Stately emerald-cut centerpiece flanked by micro-pavé crystal accents on a rich gold plated band. Anti-tarnish and waterproof for party wear.'
    },
    {
      id: 'PROD-110',
      title: 'Royal Filigree Floral Band Ring',
      sku: 'KJ-RNG-003',
      category: 'Rings',
      finish: 'Antique Matte Gold',
      guarantee: '1 Year Guarantee',
      stoneType: 'Carved Intricate Floral Motif',
      weightGrams: 3.8,
      basePrice: 1299,
      makingCharges: 0,
      discountPct: 5,
      stock: 22,
      reorderLevel: 5,
      status: 'Active',
      featured: false,
      images: [
        'assets/ring_box.jpg',
        'assets/ring.jpg',
        'assets/ring_macro.jpg',
        'assets/ring_hand.jpg'
      ],
      salesCount: 84,
      revenueGenerated: 109116,
      description: 'Floral filigree band in a matte antique gold plated finish. Comfort fit, anti-tarnish and waterproof for daily wear.'
    },
    {
      id: 'PROD-111',
      title: 'Princess Pavé Diamond Crown Ring',
      sku: 'KJ-RNG-004',
      category: 'Rings',
      finish: 'Rhodium & Gold Two-Tone',
      guarantee: '2 Years Guarantee',
      stoneType: 'Micro Pavé Swiss Zirconia Array',
      weightGrams: 4.6,
      basePrice: 1999,
      makingCharges: 0,
      discountPct: 15,
      stock: 7,
      reorderLevel: 4,
      status: 'Active',
      featured: true,
      images: [
        'assets/ring_hand.jpg',
        'assets/ring.jpg',
        'assets/ring_macro.jpg',
        'assets/ring_box.jpg'
      ],
      salesCount: 110,
      revenueGenerated: 219890,
      description: 'Arching tiara crown silhouette encrusted with micro-pavé zirconia crystals. Two-tone rhodium and gold finish.'
    },
    {
      id: 'PROD-102',
      title: 'Imperial Kundan Choker Necklace Set',
      sku: 'KJ-NCK-004',
      category: 'Necklaces',
      finish: 'Antique Matte Gold',
      guarantee: '1 Year Guarantee',
      stoneType: 'Faceted Emerald Green Stones & Pearls',
      weightGrams: 58.0,
      basePrice: 4499,
      makingCharges: 0,
      discountPct: 15,
      stock: 8,
      reorderLevel: 4,
      status: 'Active',
      featured: true,
      images: [
        'assets/necklace_emerald.jpg',
        'https://images.unsplash.com/photo-1599643478518-a784e5dc4c8f?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1617038260897-41a1f14a8ca0?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1515562141207-7a88fb7ce338?auto=format&fit=crop&w=800&q=80'
      ],
      salesCount: 94,
      revenueGenerated: 422906,
      description: 'Statement kundan-style collar necklace with matching drop jhumkas, made for festive and party wear with a 1-year colour guarantee.'
    },
    {
      id: 'PROD-112',
      title: 'Royal Peacock Temple Choker with Pearl Drops',
      sku: 'KJ-NCK-005',
      category: 'Necklaces',
      finish: '24K Micron Gold Plated',
      guarantee: '1 Year Guarantee',
      stoneType: 'Natural South Sea Simulated Pearls',
      weightGrams: 46.5,
      basePrice: 3799,
      makingCharges: 0,
      discountPct: 0,
      stock: 12,
      reorderLevel: 5,
      status: 'Active',
      featured: false,
      images: [
        'https://images.unsplash.com/photo-1599643478518-a784e5dc4c8f?auto=format&fit=crop&w=800&q=80',
        'assets/necklace_emerald.jpg',
        'https://images.unsplash.com/photo-1617038260897-41a1f14a8ca0?auto=format&fit=crop&w=800&q=80'
      ],
      salesCount: 62,
      revenueGenerated: 235538,
      description: 'Temple artisan peacock motif with cluster freshwater pearls and adjustable gilded dori cord.'
    },
    {
      id: 'PROD-113',
      title: 'Minimalist Solitaire Pendant Chain Necklace',
      sku: 'KJ-NCK-006',
      category: 'Necklaces',
      finish: 'Micro Gold Plated',
      guarantee: '1 Year Guarantee',
      stoneType: '2 Carat Equivalent Solitaire Zirconia',
      weightGrams: 8.2,
      basePrice: 1199,
      makingCharges: 0,
      discountPct: 0,
      stock: 30,
      reorderLevel: 6,
      status: 'Active',
      featured: false,
      images: [
        'https://images.unsplash.com/photo-1617038260897-41a1f14a8ca0?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1599643478518-a784e5dc4c8f?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1515562141207-7a88fb7ce338?auto=format&fit=crop&w=800&q=80'
      ],
      salesCount: 175,
      revenueGenerated: 209825,
      description: 'Delicate cable chain with a bezel-set brilliant-cut crystal pendant. Anti-tarnish and waterproof for daily wear.'
    },
    {
      id: 'PROD-103',
      title: 'Heritage Peacock Chandbali Earrings',
      sku: 'KJ-EAR-012',
      category: 'Earrings',
      finish: '24K Micron Gold Plated',
      guarantee: '1 Year Guarantee',
      stoneType: 'Ruby Red CZ & Pearl Droplets',
      weightGrams: 16.5,
      basePrice: 1399,
      makingCharges: 0,
      discountPct: 0,
      stock: 22,
      reorderLevel: 5,
      status: 'Active',
      featured: false,
      images: [
        'https://images.unsplash.com/photo-1630019852942-f89202989a59?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1535632066927-ab7c9ab60908?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1635767798638-3e25273a8236?auto=format&fit=crop&w=800&q=80'
      ],
      salesCount: 180,
      revenueGenerated: 251820,
      description: 'Traditional peacock filigree motif earrings with lightweight push back clasps, safe for sensitive ears.'
    },
    {
      id: 'PROD-114',
      title: 'Royal Jhumka Drop Earrings with Red Spinel',
      sku: 'KJ-EAR-013',
      category: 'Earrings',
      finish: 'Antique Matte Gold',
      guarantee: '1 Year Guarantee',
      stoneType: 'Faceted Red Spinel & Micro Pearls',
      weightGrams: 14.2,
      basePrice: 1599,
      makingCharges: 0,
      discountPct: 5,
      stock: 19,
      reorderLevel: 5,
      status: 'Active',
      featured: true,
      images: [
        'https://images.unsplash.com/photo-1635767798638-3e25273a8236?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1630019852942-f89202989a59?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1535632066927-ab7c9ab60908?auto=format&fit=crop&w=800&q=80'
      ],
      salesCount: 132,
      revenueGenerated: 211068,
      description: 'Classic three-tier bell jhumkas with hanging micro seed pearls and rich matte gold finish.'
    },
    {
      id: 'PROD-104',
      title: 'Broad Filigree Guarantee Bangles (Set of 4)',
      sku: 'KJ-BNG-008',
      category: 'Bangles',
      finish: 'Micro Gold Plated',
      guarantee: '1 Year Guarantee',
      stoneType: 'Embossed Floral Texture',
      weightGrams: 42.0,
      basePrice: 2499,
      makingCharges: 0,
      discountPct: 5,
      stock: 14,
      reorderLevel: 4,
      status: 'Active',
      featured: true,
      images: [
        'https://images.unsplash.com/photo-1611591475879-1662a9332e1d?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1573408301185-9146fe634ad0?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1598560917505-59a3ad559071?auto=format&fit=crop&w=800&q=80'
      ],
      salesCount: 115,
      revenueGenerated: 287385,
      description: 'Set of 4 high-shine gold plated bangles with smooth inner comfort lining. Anti-tarnish and waterproof for daily wear.'
    },
    {
      id: 'PROD-115',
      title: 'Antique Matte Gold Kada with Ruby Accents',
      sku: 'KJ-BNG-009',
      category: 'Bangles',
      finish: 'Antique Matte Gold',
      guarantee: '1 Year Guarantee',
      stoneType: 'Cabochon Ruby Stones',
      weightGrams: 32.5,
      basePrice: 1999,
      makingCharges: 0,
      discountPct: 0,
      stock: 9,
      reorderLevel: 4,
      status: 'Active',
      featured: false,
      images: [
        'https://images.unsplash.com/photo-1573408301185-9146fe634ad0?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1611591475879-1662a9332e1d?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1598560917505-59a3ad559071?auto=format&fit=crop&w=800&q=80'
      ],
      salesCount: 78,
      revenueGenerated: 155922,
      description: 'Single statement openable hinge kada featuring lion head terminals and red cabochon stones, in an antique matte gold plated finish.'
    },
    {
      id: 'PROD-105',
      title: 'Pavé Crystal Tennis Bracelet',
      sku: 'KJ-BRC-019',
      category: 'Bracelets',
      finish: 'Rhodium & Gold Two-Tone',
      guarantee: '6 Months Guarantee',
      stoneType: 'Calibrated White Crystals',
      weightGrams: 11.2,
      basePrice: 1499,
      makingCharges: 0,
      discountPct: 0,
      stock: 4,
      reorderLevel: 5,
      status: 'Active',
      featured: false,
      images: [
        'https://images.unsplash.com/photo-1598560917505-59a3ad559071?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1611591475879-1662a9332e1d?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1602751584552-8ba73aad10e1?auto=format&fit=crop&w=800&q=80'
      ],
      salesCount: 88,
      revenueGenerated: 131912,
      description: 'Flexible tennis bracelet with continuous crystal link and double security fold-over clasp.'
    },
    {
      id: 'PROD-106',
      title: 'Daily Wear Hollow Rope Chain (24 inch)',
      sku: 'KJ-CHN-003',
      category: 'Chains',
      finish: 'Micro Gold Plated',
      guarantee: '1 Year Guarantee',
      stoneType: 'Solid Diamond Cut Link',
      weightGrams: 18.5,
      basePrice: 1699,
      makingCharges: 0,
      discountPct: 0,
      stock: 26,
      reorderLevel: 6,
      status: 'Active',
      featured: true,
      images: [
        'https://images.unsplash.com/photo-1601121141461-9d6647bca1ed?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1599643478518-a784e5dc4c8f?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1506630448388-4e683c67ddb0?auto=format&fit=crop&w=800&q=80'
      ],
      salesCount: 210,
      revenueGenerated: 356790,
      description: 'Bold look, lightweight gold plated chain with a durable S-hook clasp. Anti-tarnish and waterproof for daily wear.'
    },
    {
      id: 'PROD-107',
      title: 'Floral Ghungroo Traditional Anklets (Pair)',
      sku: 'KJ-ANK-002',
      category: 'Anklets',
      finish: 'Dual Tone Micro Plated',
      guarantee: '1 Year Guarantee',
      stoneType: 'Melodious Sound Bells (Ghungroo)',
      weightGrams: 28.0,
      basePrice: 1599,
      makingCharges: 0,
      discountPct: 0,
      stock: 12,
      reorderLevel: 5,
      status: 'Active',
      featured: false,
      images: [
        'https://images.unsplash.com/photo-1535632066927-ab7c9ab60908?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1579783902614-a3fb3927b675?auto=format&fit=crop&w=800&q=80'
      ],
      salesCount: 76,
      revenueGenerated: 121524,
      description: 'Traditional payal with soft-sounding bells in an anti-tarnish gold plated finish, lovely for festive wear.'
    },
    {
      id: 'PROD-108',
      title: 'Kids Adjustable Nazariya Bangle & Ring Set',
      sku: 'KJ-KID-001',
      category: 'Kids Acccessories',
      finish: 'Skin-Friendly Gold Finish',
      guarantee: '6 Months Guarantee',
      stoneType: 'Black Protective Beads & Pink Enamel',
      weightGrams: 6.5,
      basePrice: 899,
      makingCharges: 0,
      discountPct: 0,
      stock: 20,
      reorderLevel: 5,
      status: 'Active',
      featured: true,
      images: [
        'https://images.unsplash.com/photo-1579783902614-a3fb3927b675?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1535632066927-ab7c9ab60908?auto=format&fit=crop&w=800&q=80'
      ],
      salesCount: 95,
      revenueGenerated: 85405,
      description: 'Smooth rounded edges, nickel-free and lead-free guaranteed accessories set tailored for children.'
    }
  ],

  // Stock Adjustment Audit Trail
  stockHistory: [
    {
      id: 'ADJ-104',
      date: '2026-10-12 17:30',
      sku: 'KJ-RNG-001',
      productName: 'Aura Crown Solitaire Guarantee Ring',
      change: '+10',
      previousStock: 8,
      newStock: 18,
      reason: 'Workshop Batch Delivery',
      staff: 'Priya Sharma (Inventory Lead)'
    },
    {
      id: 'ADJ-103',
      date: '2026-10-12 12:15',
      sku: 'KJ-BRC-019',
      productName: 'Pavé Crystal Tennis Bracelet',
      change: '-2',
      previousStock: 6,
      newStock: 4,
      reason: 'Online Order Fulfillment #KJ-9844',
      staff: 'System Auto-Deduct'
    },
    {
      id: 'ADJ-102',
      date: '2026-10-11 15:45',
      sku: 'KJ-CHN-003',
      productName: 'Daily Wear Hollow Rope Chain',
      change: '+15',
      previousStock: 11,
      newStock: 26,
      reason: 'Restock from Warehouse',
      staff: 'Rajesh Verma (Store Manager)'
    }
  ],

  // Customer Orders (Linked with customer accounts, with pending delivery and delivered statuses in INR)
  orders: [
    {
      id: 'KJ-9848',
      date: '2026-10-12 16:40',
      customer: {
        name: 'Ananya Deshmukh',
        email: 'ananya.d@gmail.com',
        phone: '+91 98201 12345',
        address: 'Flat 402, Lotus Heights, Bandra West, Mumbai 400050'
      },
      items: [
        {
          sku: 'KJ-RNG-001',
          title: 'Aura Crown Solitaire Guarantee Ring',
          quantity: 2,
          price: 1499,
          finish: 'Micro Gold Plated'
        }
      ],
      subtotal: 2998,
      tax: 150,
      shipping: 0,
      discount: 0,
      total: 3148,
      paymentMethod: 'UPI Direct',
      paymentStatus: 'Paid',
      status: 'Pending',
      luxuryPackaging: 'Signature Velvet Box + Guarantee Card',
      trackingNumber: '',
      notes: 'Please include 1-year guarantee stamp card.'
    },
    {
      id: 'KJ-9852',
      date: '2026-10-10 14:15',
      customer: {
        name: 'Ananya Deshmukh',
        email: 'ananya.d@gmail.com',
        phone: '+91 98201 12345',
        address: 'Flat 402, Lotus Heights, Bandra West, Mumbai 400050'
      },
      items: [
        {
          sku: 'KJ-RNG-002',
          title: 'Imperial Emerald-Cut Micro Gold Solitaire Ring',
          quantity: 1,
          price: 1899,
          finish: '24K Micron Gold Plated'
        },
        {
          sku: 'KJ-BNG-008',
          title: 'Broad Filigree Guarantee Bangles (Set of 4)',
          quantity: 1,
          price: 2499,
          finish: 'Micro Gold Plated'
        }
      ],
      subtotal: 4398,
      tax: 220,
      shipping: 0,
      discount: 0,
      total: 4618,
      paymentMethod: 'Credit Card',
      paymentStatus: 'Paid',
      status: 'Shipped',
      luxuryPackaging: 'Double Bridal Rigid Keepsake Box',
      trackingNumber: 'BLUEDART-882910',
      notes: 'Shipped via Express Courier.'
    },
    {
      id: 'KJ-9840',
      date: '2026-09-28 11:30',
      customer: {
        name: 'Ananya Deshmukh',
        email: 'ananya.d@gmail.com',
        phone: '+91 98201 12345',
        address: 'Flat 402, Lotus Heights, Bandra West, Mumbai 400050'
      },
      items: [
        {
          sku: 'KJ-NCK-004',
          title: 'Imperial Kundan Choker Necklace Set',
          quantity: 1,
          price: 4499,
          finish: 'Antique Matte Gold'
        },
        {
          sku: 'KJ-EAR-012',
          title: 'Heritage Peacock Chandbali Earrings',
          quantity: 2,
          price: 1299,
          finish: '24K Micron Gold Plated'
        }
      ],
      subtotal: 7097,
      tax: 355,
      shipping: 0,
      discount: 0,
      total: 7452,
      paymentMethod: 'UPI Direct',
      paymentStatus: 'Paid',
      status: 'Delivered',
      luxuryPackaging: 'Signature Velvet Box + Certificate',
      trackingNumber: 'DTDC-662911',
      notes: 'Delivered and acknowledged by client with 5-star rating.'
    },
    {
      id: 'KJ-9847',
      date: '2026-10-12 11:15',
      customer: {
        name: 'Meera Krishnan',
        email: 'meera.k@yahoo.com',
        phone: '+91 98450 78910',
        address: '14/B Anna Nagar West, Chennai 600040'
      },
      items: [
        {
          sku: 'KJ-NCK-004',
          title: 'Imperial Kundan Choker Necklace Set',
          quantity: 1,
          price: 4499,
          finish: 'Antique Matte Gold'
        }
      ],
      subtotal: 4499,
      tax: 225,
      shipping: 0,
      discount: 0,
      total: 4724,
      paymentMethod: 'Credit Card',
      paymentStatus: 'Paid',
      status: 'Confirmed',
      luxuryPackaging: 'Bridal Gift Box with Velvet Lining',
      trackingNumber: 'BLUEDART-892144',
      notes: 'Fast delivery requested for family event.'
    },
    {
      id: 'KJ-9841',
      date: '2026-09-15 15:20',
      customer: {
        name: 'Meera Krishnan',
        email: 'meera.k@yahoo.com',
        phone: '+91 98450 78910',
        address: '14/B Anna Nagar West, Chennai 600040'
      },
      items: [
        {
          sku: 'KJ-NCK-005',
          title: 'Royal Peacock Temple Choker with Pearl Drops',
          quantity: 2,
          price: 4299,
          finish: '24K Micron Gold Plated'
        }
      ],
      subtotal: 8598,
      tax: 430,
      shipping: 0,
      discount: 500,
      total: 8528,
      paymentMethod: 'Net Banking',
      paymentStatus: 'Paid',
      status: 'Delivered',
      luxuryPackaging: 'Signature Velvet Box',
      trackingNumber: 'DELHIVERY-991204',
      notes: 'Previous order delivered successfully.'
    },
    {
      id: 'KJ-9846',
      date: '2026-10-11 19:22',
      customer: {
        name: 'Shweta Singhal',
        email: 'shweta.s@outlook.com',
        phone: '+91 98110 54321',
        address: 'C-82 South Extension Part II, New Delhi 110049'
      },
      items: [
        {
          sku: 'KJ-BNG-008',
          title: 'Broad Filigree Guarantee Bangles (Set of 4)',
          quantity: 1,
          price: 2499,
          finish: 'Micro Gold Plated'
        }
      ],
      subtotal: 2499,
      tax: 125,
      shipping: 0,
      discount: 0,
      total: 2624,
      paymentMethod: 'Net Banking',
      paymentStatus: 'Paid',
      status: 'Processing',
      luxuryPackaging: 'Rigid Jewelry Gift Box',
      trackingNumber: 'DTDC-VLT-901128',
      notes: 'Bangle Size: 2.6'
    },
    {
      id: 'KJ-9842',
      date: '2026-08-20 17:00',
      customer: {
        name: 'Shweta Singhal',
        email: 'shweta.s@outlook.com',
        phone: '+91 98110 54321',
        address: 'C-82 South Extension Part II, New Delhi 110049'
      },
      items: [
        {
          sku: 'KJ-CHN-003',
          title: 'Daily Wear Hollow Rope Chain (24 inch)',
          quantity: 3,
          price: 1199,
          finish: 'Micro Gold Plated'
        }
      ],
      subtotal: 3597,
      tax: 180,
      shipping: 0,
      discount: 0,
      total: 3777,
      paymentMethod: 'UPI Direct',
      paymentStatus: 'Paid',
      status: 'Delivered',
      luxuryPackaging: 'Velvet Pouches Set',
      trackingNumber: 'BLUEDART-332910',
      notes: 'Delivered in August.'
    },
    {
      id: 'KJ-9845',
      date: '2026-10-10 13:05',
      customer: {
        name: 'Pooja Hegde',
        email: 'pooja.h@gmail.com',
        phone: '+91 97400 33221',
        address: '45 Indiranagar 100ft Road, Bangalore 560038'
      },
      items: [
        {
          sku: 'KJ-CHN-003',
          title: 'Daily Wear Hollow Rope Chain',
          quantity: 2,
          price: 1199,
          finish: 'Micro Gold Plated'
        }
      ],
      subtotal: 2398,
      tax: 120,
      shipping: 0,
      discount: 200,
      total: 2318,
      paymentMethod: 'Cash on Delivery',
      paymentStatus: 'Paid',
      status: 'Shipped',
      luxuryPackaging: 'Velvet Pouch Packaging',
      trackingNumber: 'DELHIVERY-44781',
      notes: 'Dispatched via express courier.'
    },
    {
      id: 'KJ-9850',
      date: '2026-10-12 18:20',
      customer: {
        name: 'Rajeshwari Sen',
        email: 'rajeshwari.sen@gmail.com',
        phone: '+91 98300 77112',
        address: '92 Park Street, 3rd Floor, Kolkata 700016'
      },
      items: [
        {
          sku: 'KJ-RNG-004',
          title: 'Princess Pavé Diamond Crown Ring',
          quantity: 1,
          price: 1999,
          finish: 'Rhodium & Gold Two-Tone'
        },
        {
          sku: 'KJ-ANK-002',
          title: 'Floral Ghungroo Traditional Anklets (Pair)',
          quantity: 1,
          price: 1499,
          finish: 'Dual Tone Micro Plated'
        }
      ],
      subtotal: 3498,
      tax: 175,
      shipping: 0,
      discount: 250,
      total: 3423,
      paymentMethod: 'UPI Direct',
      paymentStatus: 'Paid',
      status: 'Pending',
      luxuryPackaging: 'Bridal Gift Box with Velvet Lining',
      trackingNumber: '',
      notes: 'New VIP customer order, awaiting packaging check.'
    },
    {
      id: 'KJ-9844',
      date: '2026-10-08 10:30',
      customer: {
        name: 'Sunita Rawat',
        email: 'sunita.rawat@gmail.com',
        phone: '+91 99280 66554',
        address: '72 Civil Lines, Jaipur 302006'
      },
      items: [
        {
          sku: 'KJ-KID-001',
          title: 'Kids Adjustable Nazariya Bangle & Ring Set',
          quantity: 1,
          price: 899,
          finish: 'Skin-Friendly Gold Finish'
        }
      ],
      subtotal: 899,
      tax: 45,
      shipping: 0,
      discount: 0,
      total: 944,
      paymentMethod: 'UPI Direct',
      paymentStatus: 'Paid',
      status: 'Delivered',
      luxuryPackaging: 'Kids Special Cartoon Gift Box',
      trackingNumber: 'EKART-771239',
      notes: 'Delivered and verified by client.'
    },
    {
      id: 'KJ-9839',
      date: '2026-10-02 14:10',
      customer: {
        name: 'Vikram Malhotra',
        email: 'vikram.m@gmail.com',
        phone: '+91 98210 99887',
        address: 'B-12 Vasant Vihar, New Delhi 110057'
      },
      items: [
        {
          sku: 'KJ-BRC-019',
          title: 'Pavé Crystal Tennis Bracelet',
          quantity: 1,
          price: 1599,
          finish: 'Rhodium & Gold Two-Tone'
        }
      ],
      subtotal: 1599,
      tax: 80,
      shipping: 0,
      discount: 0,
      total: 1679,
      paymentMethod: 'Credit Card',
      paymentStatus: 'Refunded',
      status: 'Cancelled',
      luxuryPackaging: 'Standard Box',
      trackingNumber: '',
      notes: 'Client changed wrist size preference, refund processed.'
    }
  ],

  // Customers with Signed-in status, devices, addresses, activity logs, and orders linkage
  customers: [
    {
      id: 'CUST-001',
      name: 'Ananya Deshmukh',
      email: 'ananya.d@gmail.com',
      phone: '+91 98201 12345',
      tier: 'VIP Patron',
      status: 'Active',
      isBlocked: false,
      blockedReason: '',
      blockedAt: '',
      blockedBy: '',
      isSignedIn: true,
      lastLogin: 'Active now',
      sessionDevice: 'iPhone 15 Pro • Kinz iOS App',
      ipAddress: '103.21.24.8 (Mumbai, MH)',
      totalSpent: 15218,
      totalOrders: 3,
      registeredDate: '2025-01-15',
      registrationMethod: 'Mobile OTP Verified',
      registrationChannel: 'Kinz iOS App',
      kycStatus: 'Verified VIP',
      regCode: 'KINZ-REG-2025-0115-AD',
      primaryAddress: 'Flat 402, Lotus Heights, Bandra West, Mumbai 400050',
      notes: 'Frequent buyer of gold plated rings and matching bangles. Prefers express courier dispatch.',
      preferences: {
        ringSize: '14 (US 7)',
        bangleSize: '2.6',
        preferredFinish: '24K Micron Gold Plated',
        favoriteCategory: 'Solitaire Rings & Filigree Bangles',
        packaging: 'Signature Velvet Rigid Keepsake Box'
      },
      addresses: [
        {
          id: 'ADDR-001-1',
          label: 'Home (Primary)',
          name: 'Ananya Deshmukh',
          phone: '+91 98201 12345',
          street: 'Flat 402, Lotus Heights, Perry Cross Road',
          landmark: 'Near Mehboob Studio',
          city: 'Mumbai',
          state: 'Maharashtra',
          pincode: '400050',
          isDefault: true,
          type: 'Shipping & Billing'
        },
        {
          id: 'ADDR-001-2',
          label: 'Design Studio / Office',
          name: 'Ananya Deshmukh',
          phone: '+91 98201 12345',
          street: 'Level 8, Platina Tower, G Block, BKC',
          landmark: 'Opposite Bharat Diamond Bourse',
          city: 'Mumbai',
          state: 'Maharashtra',
          pincode: '400051',
          isDefault: false,
          type: 'Shipping Only'
        }
      ],
      activityLog: [
        {
          id: 'ACT-001-1',
          date: '2026-10-16 18:20',
          action: 'Session Login',
          badge: 'Active Now',
          type: 'auth',
          details: 'Signed in from iPhone 15 Pro • Kinz iOS App (IP: 103.21.24.8, Mumbai)',
          ipDevice: 'iPhone 15 Pro (iOS 18)'
        },
        {
          id: 'ACT-001-2',
          date: '2026-10-12 16:40',
          action: 'Order Placed',
          badge: '₹3,148',
          type: 'order',
          details: 'Placed Order #KJ-9848 for 2x Aura Crown Solitaire Guarantee Rings (UPI Direct)',
          ipDevice: 'iPhone 15 Pro'
        },
        {
          id: 'ACT-001-3',
          date: '2026-10-12 16:32',
          action: 'Applied Coupon',
          badge: 'FESTIVE15',
          type: 'cart',
          details: 'Applied promotional coupon FESTIVE15 for store checkout',
          ipDevice: 'iPhone 15 Pro'
        },
        {
          id: 'ACT-001-4',
          date: '2026-10-10 14:15',
          action: 'Order Placed',
          badge: '₹4,618',
          type: 'order',
          details: 'Placed Order #KJ-9852 (Emerald-Cut Solitaire Ring + Broad Filigree Bangles)',
          ipDevice: 'iPhone 15 Pro'
        },
        {
          id: 'ACT-001-5',
          date: '2026-10-02 11:30',
          action: 'Address Added',
          badge: 'New Address',
          type: 'address',
          details: 'Added secondary delivery address: Platina Tower, BKC, Mumbai',
          ipDevice: 'Safari Web'
        },
        {
          id: 'ACT-001-6',
          date: '2026-09-28 11:30',
          action: 'Order Placed',
          badge: '₹7,452',
          type: 'order',
          details: 'Placed Order #KJ-9840 (Imperial Kundan Choker Necklace Set)',
          ipDevice: 'iPhone 15 Pro'
        },
        {
          id: 'ACT-001-7',
          date: '2025-01-15 10:00',
          action: 'Account Created',
          badge: 'VIP Verified',
          type: 'account',
          details: 'Customer profile registered and upgraded to VIP Patron status',
          ipDevice: 'Mobile OTP Signup'
        }
      ]
    },
    {
      id: 'CUST-002',
      name: 'Meera Krishnan',
      email: 'meera.k@yahoo.com',
      phone: '+91 98450 78910',
      tier: 'Gold Club',
      status: 'Active',
      isBlocked: false,
      blockedReason: '',
      blockedAt: '',
      blockedBy: '',
      isSignedIn: true,
      lastLogin: 'Active now',
      sessionDevice: 'MacBook Air • Chrome Web',
      ipAddress: '115.240.18.92 (Chennai, TN)',
      totalSpent: 13252,
      totalOrders: 2,
      registeredDate: '2025-03-20',
      registrationMethod: 'Email Verified',
      registrationChannel: 'Website (Desktop)',
      kycStatus: 'Verified Member',
      regCode: 'KINZ-REG-2025-0320-MK',
      primaryAddress: '14/B Anna Nagar West, Chennai 600040',
      notes: 'Prefers matte finish antique jewellery with emerald green stones and bridal chokers.',
      preferences: {
        ringSize: '16 (US 8)',
        bangleSize: '2.8',
        preferredFinish: 'Matte Antique 24K Plated',
        favoriteCategory: 'Bridal Chokers & Jhumkas',
        packaging: 'Double Bridal Rigid Keepsake Box'
      },
      addresses: [
        {
          id: 'ADDR-002-1',
          label: 'Residence (Default)',
          name: 'Meera Krishnan',
          phone: '+91 98450 78910',
          street: '14/B, 4th Main Road, Anna Nagar West',
          landmark: 'Near Roundtana Park',
          city: 'Chennai',
          state: 'Tamil Nadu',
          pincode: '600040',
          isDefault: true,
          type: 'Shipping & Billing'
        },
        {
          id: 'ADDR-002-2',
          label: 'Family Home (Alwarpet)',
          name: 'Meera Krishnan',
          phone: '+91 98450 78910',
          street: '22 Oliver Road, Alwarpet',
          landmark: 'Behind Music Academy',
          city: 'Chennai',
          state: 'Tamil Nadu',
          pincode: '600018',
          isDefault: false,
          type: 'Shipping Only'
        }
      ],
      activityLog: [
        {
          id: 'ACT-002-1',
          date: '2026-10-16 18:05',
          action: 'Session Login',
          badge: 'Active Now',
          type: 'auth',
          details: 'Signed in from MacBook Air • Chrome Web (IP: 115.240.18.92, Chennai)',
          ipDevice: 'macOS Chrome 128'
        },
        {
          id: 'ACT-002-2',
          date: '2026-10-12 11:15',
          action: 'Order Placed',
          badge: '₹4,723',
          type: 'order',
          details: 'Placed Order #KJ-9847 (Peacock Temple Haram + Broad Filigree Bangles)',
          ipDevice: 'MacBook Air'
        },
        {
          id: 'ACT-002-3',
          date: '2026-10-12 11:00',
          action: 'Item Viewed',
          badge: 'Browsing',
          type: 'cart',
          details: 'Explored Antique Gold Plated Festive Collections and added Jhumkas to wishlist',
          ipDevice: 'MacBook Air'
        },
        {
          id: 'ACT-002-4',
          date: '2026-09-15 15:45',
          action: 'Order Placed',
          badge: '₹8,529',
          type: 'order',
          details: 'Placed Order #KJ-9835 (Heritage Royal Solitaire Necklace Suite)',
          ipDevice: 'MacBook Air'
        },
        {
          id: 'ACT-002-5',
          date: '2025-03-20 14:10',
          action: 'Account Created',
          badge: 'Gold Club',
          type: 'account',
          details: 'Customer profile registered and verified via email authentication',
          ipDevice: 'Desktop Chrome'
        }
      ]
    },
    {
      id: 'CUST-003',
      name: 'Shweta Singhal',
      email: 'shweta.s@outlook.com',
      phone: '+91 98110 54321',
      tier: 'Regular Customer',
      status: 'Active',
      isBlocked: false,
      blockedReason: '',
      blockedAt: '',
      blockedBy: '',
      isSignedIn: true,
      lastLogin: 'Signed in 6 mins ago',
      sessionDevice: 'Samsung Galaxy S24 • Mobile Web',
      ipAddress: '122.161.45.10 (New Delhi, DL)',
      totalSpent: 6401,
      totalOrders: 2,
      registeredDate: '2025-05-08',
      registrationMethod: 'Google One-Tap',
      registrationChannel: 'Mobile Web (Android)',
      kycStatus: 'Verified Member',
      regCode: 'KINZ-REG-2025-0508-SS',
      primaryAddress: 'C-82 South Extension Part II, New Delhi 110049',
      notes: 'Purchases daily wear chains and guarantee bangles in size 2.6.',
      preferences: {
        ringSize: '12 (US 6)',
        bangleSize: '2.6',
        preferredFinish: 'High Polish 24K Micron Gold',
        favoriteCategory: 'Daily Wear Chains & Bangles',
        packaging: 'Signature Velvet Box'
      },
      addresses: [
        {
          id: 'ADDR-003-1',
          label: 'Residence (Default)',
          name: 'Shweta Singhal',
          phone: '+91 98110 54321',
          street: 'C-82 South Extension Part II, Ring Road',
          landmark: 'Near South Ex Market Metro',
          city: 'New Delhi',
          state: 'Delhi',
          pincode: '110049',
          isDefault: true,
          type: 'Shipping & Billing'
        }
      ],
      activityLog: [
        {
          id: 'ACT-003-1',
          date: '2026-10-16 18:45',
          action: 'Session Login',
          badge: 'Online',
          type: 'auth',
          details: 'Signed in from Samsung Galaxy S24 • Chrome Mobile',
          ipDevice: 'Android 14 • Chrome'
        },
        {
          id: 'ACT-003-2',
          date: '2026-10-11 09:30',
          action: 'Order Placed',
          badge: '₹2,623',
          type: 'order',
          details: 'Placed Order #KJ-9846 (Broad Filigree Guarantee Bangles)',
          ipDevice: 'Samsung Galaxy S24'
        },
        {
          id: 'ACT-003-3',
          date: '2026-08-14 18:20',
          action: 'Order Placed',
          badge: '₹3,778',
          type: 'order',
          details: 'Placed Order #KJ-9820 (Royal Heritage Filigree Chain & Pendant)',
          ipDevice: 'Samsung Galaxy S24'
        },
        {
          id: 'ACT-003-4',
          date: '2025-05-08 16:00',
          action: 'Account Created',
          badge: 'Registered',
          type: 'account',
          details: 'Customer profile registered online',
          ipDevice: 'Mobile Web'
        }
      ]
    },
    {
      id: 'CUST-004',
      name: 'Pooja Hegde',
      email: 'pooja.h@gmail.com',
      phone: '+91 97400 33221',
      tier: 'Royal Platinum',
      status: 'Active',
      isBlocked: false,
      blockedReason: '',
      blockedAt: '',
      blockedBy: '',
      isSignedIn: true,
      lastLogin: 'Signed in 15 mins ago',
      sessionDevice: 'iPad Pro • Safari Web',
      ipAddress: '49.207.195.4 (Bangalore, KA)',
      totalSpent: 2318,
      totalOrders: 1,
      registeredDate: '2025-06-12',
      registrationMethod: 'Personal Invitation',
      registrationChannel: 'Customer Support',
      kycStatus: 'Verified VIP',
      regCode: 'KINZ-REG-2025-0612-PH',
      primaryAddress: '45 Indiranagar 100ft Road, Bangalore 560038',
      notes: 'Celebrity stylist. Appreciates discreet packaging with guarantee certificate.',
      preferences: {
        ringSize: '13 (US 6.5)',
        bangleSize: '2.4',
        preferredFinish: '24K Micron Gold Plated',
        favoriteCategory: 'Kundan Necklaces & Solitaire Rings',
        packaging: 'Signature Velvet Rigid Keepsake Box + Wax Seal'
      },
      addresses: [
        {
          id: 'ADDR-004-1',
          label: 'Penthouse Residence (Default)',
          name: 'Pooja Hegde',
          phone: '+91 97400 33221',
          street: '45 Indiranagar 100ft Road, 4th Stage',
          landmark: 'Opposite Corner House',
          city: 'Bangalore',
          state: 'Karnataka',
          pincode: '560038',
          isDefault: true,
          type: 'Shipping & Billing'
        },
        {
          id: 'ADDR-004-2',
          label: 'Styling Studio',
          name: 'Pooja Hegde Concierge',
          phone: '+91 97400 33221',
          street: '12 Lavelle Road, Richmond Circle',
          landmark: 'Near UB City',
          city: 'Bangalore',
          state: 'Karnataka',
          pincode: '560001',
          isDefault: false,
          type: 'Shipping Only'
        }
      ],
      activityLog: [
        {
          id: 'ACT-004-1',
          date: '2026-10-16 18:35',
          action: 'Session Login',
          badge: 'Online',
          type: 'auth',
          details: 'Signed in from iPad Pro • Safari Web (IP: 49.207.195.4, Bangalore)',
          ipDevice: 'iPadOS Safari'
        },
        {
          id: 'ACT-004-2',
          date: '2026-10-09 17:50',
          action: 'Order Placed',
          badge: '₹2,318',
          type: 'order',
          details: 'Placed Order #KJ-9844 (Imperial Emerald-Cut Micro Gold Solitaire Ring)',
          ipDevice: 'iPad Pro'
        },
        {
          id: 'ACT-004-3',
          date: '2026-10-09 17:42',
          action: 'Address Added',
          badge: 'New Address',
          type: 'address',
          details: 'Saved Lavelle Road styling studio address for quick delivery',
          ipDevice: 'iPad Pro'
        },
        {
          id: 'ACT-004-4',
          date: '2025-06-12 11:20',
          action: 'Account Created',
          badge: 'Royal Platinum',
          type: 'account',
          details: 'Upgraded to Royal Platinum VIP status upon personal concierge invitation',
          ipDevice: 'Concierge Portal'
        }
      ]
    },
    {
      id: 'CUST-005',
      name: 'Sunita Rawat',
      email: 'sunita.rawat@gmail.com',
      phone: '+91 99280 66554',
      tier: 'Gold Club',
      status: 'Active',
      isBlocked: false,
      blockedReason: '',
      blockedAt: '',
      blockedBy: '',
      isSignedIn: false,
      lastLogin: 'Offline • 3 hours ago',
      sessionDevice: 'Windows 11 • Edge',
      ipAddress: '14.139.240.12 (Jaipur, RJ)',
      totalSpent: 944,
      totalOrders: 1,
      registeredDate: '2025-07-22',
      registrationMethod: 'Mobile OTP Verified',
      registrationChannel: 'Website (Desktop)',
      kycStatus: 'Verified Member',
      regCode: 'KINZ-REG-2025-0722-SR',
      primaryAddress: '72 Civil Lines, Jaipur 302006',
      notes: 'Loves traditional ghungroo payals and kids guarantee accessories.',
      preferences: {
        ringSize: '15 (US 7.5)',
        bangleSize: '2.6',
        preferredFinish: 'Antique Rose Gold & Yellow Gold',
        favoriteCategory: 'Payals & Traditional Bangles',
        packaging: 'Signature Velvet Box'
      },
      addresses: [
        {
          id: 'ADDR-005-1',
          label: 'Home (Default)',
          name: 'Sunita Rawat',
          phone: '+91 99280 66554',
          street: '72 Civil Lines, Raj Bhavan Road',
          landmark: 'Opposite Pink City Club',
          city: 'Jaipur',
          state: 'Rajasthan',
          pincode: '302006',
          isDefault: true,
          type: 'Shipping & Billing'
        }
      ],
      activityLog: [
        {
          id: 'ACT-005-1',
          date: '2026-10-16 15:40',
          action: 'Session Logout',
          badge: 'Offline',
          type: 'auth',
          details: 'Session ended from Windows 11 PC (Edge Browser)',
          ipDevice: 'Windows 11 Edge'
        },
        {
          id: 'ACT-005-2',
          date: '2026-10-08 12:20',
          action: 'Order Placed',
          badge: '₹944',
          type: 'order',
          details: 'Placed Order #KJ-9843 (Traditional Antique Ghungroo Payal)',
          ipDevice: 'Windows 11 PC'
        },
        {
          id: 'ACT-005-3',
          date: '2025-07-22 14:00',
          action: 'Account Created',
          badge: 'Registered',
          type: 'account',
          details: 'Account registered and mobile number verified via OTP',
          ipDevice: 'Web Browser'
        }
      ]
    },
    {
      id: 'CUST-006',
      name: 'Rajeshwari Sen',
      email: 'rajeshwari.sen@gmail.com',
      phone: '+91 98300 77112',
      tier: 'VIP Patron',
      status: 'Active',
      isBlocked: false,
      blockedReason: '',
      blockedAt: '',
      blockedBy: '',
      isSignedIn: false,
      lastLogin: 'Offline • 1 day ago',
      sessionDevice: 'iPhone 14 • Safari',
      ipAddress: '103.221.254.6 (Kolkata, WB)',
      totalSpent: 3423,
      totalOrders: 1,
      registeredDate: '2025-08-10',
      registrationMethod: 'Apple ID Verified',
      registrationChannel: 'Kinz iOS App',
      kycStatus: 'Verified VIP',
      regCode: 'KINZ-REG-2025-0810-RS',
      primaryAddress: '92 Park Street, 3rd Floor, Kolkata 700016',
      notes: 'Regular high-value festive shopper. Orders authentic guarantee sets for Durga Puja and weddings.',
      preferences: {
        ringSize: '14 (US 7)',
        bangleSize: '2.6',
        preferredFinish: '24K Micron Gold Plated',
        favoriteCategory: 'Kundan Chokers & Jhumkas',
        packaging: 'Double Bridal Rigid Keepsake Box'
      },
      addresses: [
        {
          id: 'ADDR-006-1',
          label: 'Residence (Default)',
          name: 'Rajeshwari Sen',
          phone: '+91 98300 77112',
          street: '92 Park Street, 3rd Floor, Mansions',
          landmark: 'Near Flurys Bakery',
          city: 'Kolkata',
          state: 'West Bengal',
          pincode: '700016',
          isDefault: true,
          type: 'Shipping & Billing'
        },
        {
          id: 'ADDR-006-2',
          label: 'South Kolkata Vacation Villa',
          name: 'Rajeshwari Sen',
          phone: '+91 98300 77112',
          street: '18 Ballygunge Circular Road',
          landmark: 'Near Birla Temple',
          city: 'Kolkata',
          state: 'West Bengal',
          pincode: '700019',
          isDefault: false,
          type: 'Shipping Only'
        }
      ],
      activityLog: [
        {
          id: 'ACT-006-1',
          date: '2026-10-15 16:30',
          action: 'Session Login',
          badge: 'Offline',
          type: 'auth',
          details: 'Browsed Festive Durga Puja Solitaire Collection for 22 minutes',
          ipDevice: 'iPhone 14 Safari'
        },
        {
          id: 'ACT-006-2',
          date: '2026-10-07 10:45',
          action: 'Order Placed',
          badge: '₹3,423',
          type: 'order',
          details: 'Placed Order #KJ-9842 (Aura Crown Solitaire Ring + Peacock Chandbalis)',
          ipDevice: 'iPhone 14'
        },
        {
          id: 'ACT-006-3',
          date: '2025-08-10 12:15',
          action: 'Account Created',
          badge: 'VIP Patron',
          type: 'account',
          details: 'Joined Kinz Jewellery Royal Club',
          ipDevice: 'iPhone App'
        }
      ]
    },
    {
      id: 'CUST-007',
      name: 'Vikram Malhotra',
      email: 'vikram.m@gmail.com',
      phone: '+91 98210 99887',
      tier: 'VIP',
      status: 'Active',
      isBlocked: false,
      blockedReason: '',
      blockedAt: '',
      blockedBy: '',
      isSignedIn: false,
      lastLogin: 'Offline • 2 days ago',
      sessionDevice: 'Android App',
      ipAddress: '103.44.112.9 (New Delhi, DL)',
      totalSpent: 1679,
      totalOrders: 1,
      registeredDate: '2025-09-01',
      registrationMethod: 'Mobile OTP Verified',
      registrationChannel: 'Android App',
      kycStatus: 'Verified Member',
      regCode: 'KINZ-REG-2025-0901-VM',
      primaryAddress: 'B-12 Vasant Vihar, New Delhi 110057',
      notes: 'Orders men chains and unisex tennis bracelets.',
      preferences: {
        ringSize: '20 (US 10)',
        bangleSize: '2.8',
        preferredFinish: '24K Micron Gold Plated',
        favoriteCategory: 'Men Chains & Tennis Bracelets',
        packaging: 'Signature Velvet Box'
      },
      addresses: [
        {
          id: 'ADDR-007-1',
          label: 'Residence (Default)',
          name: 'Vikram Malhotra',
          phone: '+91 98210 99887',
          street: 'B-12 Vasant Vihar, Poorvi Marg',
          landmark: 'Near Priya Cinema Market',
          city: 'New Delhi',
          state: 'Delhi',
          pincode: '110057',
          isDefault: true,
          type: 'Shipping & Billing'
        }
      ],
      activityLog: [
        {
          id: 'ACT-007-1',
          date: '2026-10-14 11:30',
          action: 'Account Verified',
          badge: 'Active',
          type: 'security',
          details: 'Account identity verified and active status confirmed.',
          ipDevice: 'Admin Console'
        },
        {
          id: 'ACT-007-2',
          date: '2026-10-13 19:10',
          action: 'Payment Dispute',
          badge: 'Alert Flag',
          type: 'security',
          details: 'Initiated unauthorized chargeback dispute on Order #KJ-9841 for ₹1,679',
          ipDevice: 'Payment Gateway'
        },
        {
          id: 'ACT-007-3',
          date: '2026-10-06 14:15',
          action: 'Order Placed',
          badge: '₹1,679',
          type: 'order',
          details: 'Placed Order #KJ-9841 (Peacock Temple Haram Chain)',
          ipDevice: 'Android App'
        },
        {
          id: 'ACT-007-4',
          date: '2025-09-01 11:00',
          action: 'Account Created',
          badge: 'VIP',
          type: 'account',
          details: 'Account created through Android Mobile App',
          ipDevice: 'Android App'
        }
      ]
    },
    {
      id: 'CUST-008',
      name: 'Tara Sutaria',
      email: 'tara.s@kinzjewellery.com',
      phone: '+91 98200 44332',
      tier: 'Gold Club',
      status: 'Active',
      isBlocked: false,
      blockedReason: '',
      blockedAt: '',
      blockedBy: '',
      isSignedIn: true,
      lastLogin: 'Active now',
      sessionDevice: 'iPhone 16 Pro • Kinz iOS App',
      ipAddress: '103.25.40.12 (Mumbai, MH)',
      totalSpent: 4899,
      totalOrders: 1,
      registeredDate: '2026-09-18',
      registrationMethod: 'Apple ID Verified',
      registrationChannel: 'Kinz iOS App',
      kycStatus: 'Verified Member',
      regCode: 'KINZ-REG-2026-0918-TS',
      primaryAddress: 'Tower 4, BeauMonde Towers, Prabhadevi, Mumbai 400025',
      notes: 'New royal signup looking for bridal filigree choker sets and micro-gold solitaires.',
      preferences: {
        ringSize: '13 (US 6.5)',
        bangleSize: '2.4',
        preferredFinish: '24K Micron Gold Plated',
        favoriteCategory: 'Bridal Chokers & Jhumkas',
        packaging: 'Signature Velvet Rigid Keepsake Box'
      },
      addresses: [
        {
          id: 'ADDR-008-1',
          label: 'Apartment (Default)',
          name: 'Tara Sutaria',
          phone: '+91 98200 44332',
          street: 'Tower 4, BeauMonde Towers, Appasaheb Marathe Marg',
          landmark: 'Near Siddhivinayak Temple',
          city: 'Mumbai',
          state: 'Maharashtra',
          pincode: '400025',
          isDefault: true,
          type: 'Shipping & Billing'
        }
      ],
      activityLog: [
        {
          id: 'ACT-008-1',
          date: '2026-10-16 18:50',
          action: 'Session Login',
          badge: 'Online Now',
          type: 'auth',
          details: 'Logged into Kinz iOS App from iPhone 16 Pro (FaceID verified)',
          ipDevice: 'iPhone 16 Pro (iOS 18.1)'
        },
        {
          id: 'ACT-008-2',
          date: '2026-10-10 15:20',
          action: 'Order Placed',
          badge: '₹4,899',
          type: 'order',
          details: 'Placed Order #KJ-9850 (Imperial Kundan Choker Necklace Set)',
          ipDevice: 'iPhone 16 Pro'
        },
        {
          id: 'ACT-008-3',
          date: '2026-09-18 10:15',
          action: 'Account Registered',
          badge: 'Apple ID',
          type: 'account',
          details: 'New registration completed via Apple ID One-Touch on Kinz iOS App',
          ipDevice: 'Kinz iOS App'
        }
      ]
    },
    {
      id: 'CUST-009',
      name: 'Kabir Oberoi',
      email: 'kabir.oberoi@fintech.co',
      phone: '+91 99100 88776',
      tier: 'Regular Customer',
      status: 'Active',
      isBlocked: false,
      blockedReason: '',
      blockedAt: '',
      blockedBy: '',
      isSignedIn: false,
      lastLogin: 'Yesterday 19:40',
      sessionDevice: 'MacBook Pro • Chrome Web',
      ipAddress: '182.73.120.45 (Gurugram, HR)',
      totalSpent: 1999,
      totalOrders: 1,
      registeredDate: '2026-10-04',
      registrationMethod: 'Google One-Tap',
      registrationChannel: 'Website',
      kycStatus: 'Verified Member',
      regCode: 'KINZ-REG-2026-1004-KO',
      primaryAddress: 'Villa 14, Magnolias, Golf Course Road, Gurugram 122002',
      notes: 'Anniversary gift buyer. Express courier delivery required.',
      preferences: {
        ringSize: '18 (US 9)',
        bangleSize: '2.6',
        preferredFinish: 'High Polish Gold',
        favoriteCategory: 'Solitaire Rings',
        packaging: 'Velvet Rigid Keepsake Box'
      },
      addresses: [
        {
          id: 'ADDR-009-1',
          label: 'Residence (Default)',
          name: 'Kabir Oberoi',
          phone: '+91 99100 88776',
          street: 'Villa 14, The Magnolias, DLF Phase 5',
          landmark: 'Near Horizon Center',
          city: 'Gurugram',
          state: 'Haryana',
          pincode: '122002',
          isDefault: true,
          type: 'Shipping & Billing'
        }
      ],
      activityLog: [
        {
          id: 'ACT-009-1',
          date: '2026-10-15 19:40',
          action: 'Session Login',
          badge: 'Offline',
          type: 'auth',
          details: 'Browsed royal solitaire rings from Chrome Web on macOS',
          ipDevice: 'MacBook Pro Chrome'
        },
        {
          id: 'ACT-009-2',
          date: '2026-10-05 14:10',
          action: 'Order Placed',
          badge: '₹1,999',
          type: 'order',
          details: 'Placed Order #KJ-9849 (Princess Pavé Diamond Crown Ring)',
          ipDevice: 'Chrome Web'
        },
        {
          id: 'ACT-009-3',
          date: '2026-10-04 18:30',
          action: 'Account Registered',
          badge: 'Google Verified',
          type: 'account',
          details: 'Registered customer profile via Google One-Tap authentication',
          ipDevice: 'Website'
        }
      ]
    }
  ],

  // Coupons
  coupons: [
    {
      id: 'CPN-01',
      code: 'FESTIVE15',
      discountType: 'Percentage',
      value: 15,
      minOrder: 1999,
      applicableTo: 'All Product Categories',
      usedCount: 78,
      usageLimit: 200,
      expiresAt: '2026-12-31',
      status: 'Active'
    },
    {
      id: 'CPN-02',
      code: 'BRIDAL50',
      discountType: 'Flat',
      value: 500,
      minOrder: 3999,
      applicableTo: 'Necklaces & Bangles',
      usedCount: 34,
      usageLimit: 100,
      expiresAt: '2026-11-30',
      status: 'Active'
    },
    {
      id: 'CPN-03',
      code: 'WELCOME10',
      discountType: 'Percentage',
      value: 10,
      minOrder: 999,
      applicableTo: 'All Categories',
      usedCount: 142,
      usageLimit: 300,
      expiresAt: '2026-12-15',
      status: 'Active'
    }
  ],

  // Admin Team (Super Admin and Admin only)
  adminUsers: [
    {
      id: 'USR-01',
      name: 'Alexander Kinz',
      role: 'Super Admin',
      email: 'alexander@kinzjewellery.com',
      status: 'Active',
      lastLogin: 'Today at 19:45'
    },
    {
      id: 'USR-02',
      name: 'Priya Sharma',
      role: 'Admin',
      email: 'priya@kinzjewellery.com',
      status: 'Active',
      lastLogin: 'Today at 17:30'
    }
  ],

  // Store Settings (INR Exclusive)
  settings: {
    storeName: 'Kinz Guarantee Jewellery',
    officialEmail: 'support@kinzjewellery.com',
    phone: '+91 98201 12345',
    currency: 'INR',
    currencySymbol: '₹',
    guaranteePolicy: '1 Year Polish & Color Guarantee Certificate',
    standardTaxRate: 3.0
  },

  // Chart Analytics Historical Series (INR)
  chartData: {
    '7days': {
      labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
      revenue: [45000, 62000, 48000, 78000, 95000, 118000, 84000],
      orders: [22, 31, 26, 42, 54, 68, 45]
    },
    '30days': {
      labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
      revenue: [240000, 360000, 315000, 485000],
      orders: [140, 195, 172, 260]
    },
    '1year': {
      labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
      revenue: [195000, 245000, 280000, 320000, 355000, 390000, 425000, 395000, 455000, 520000, 610000, 700000],
      orders: [110, 132, 150, 178, 195, 214, 230, 215, 250, 285, 330, 380]
    },
    categoryShare: [
      { category: 'Necklaces', value: 32, color: '#18352D' },
      { category: 'Bangles', value: 24, color: '#C87927' },
      { category: 'Earrings', value: 20, color: '#D4A62A' },
      { category: 'Chains & Others', value: 24, color: '#172B28' }
    ]
  }
};

class KinzStore {
  constructor() {
    this.data = this.load();
  }

  // This legacy data helper intentionally does not read from or write to browser storage.
  // Persistent application data must be loaded and saved through the PHP/MySQL backend.
  load() {
    return JSON.parse(JSON.stringify(defaultJewelleryData));
  }

  // Keep changes in memory only. Use the server-side PHP endpoints for persistence.
  save(newData) {
    if (newData) this.data = newData;
    return this.data;
  }

  reset() {
    this.data = JSON.parse(JSON.stringify(defaultJewelleryData));
    return this.data;
  }

  formatCurrency(num) {
    const symbol = this.data.settings?.currencySymbol || '₹';
    const n = Number(num || 0);
    const hasDecimals = n % 1 !== 0;
    return symbol + n.toLocaleString('en-IN', {
      minimumFractionDigits: hasDecimals ? 2 : 0,
      maximumFractionDigits: 2
    });
  }
}

// Global store instance
window.kinzDB = new KinzStore();
