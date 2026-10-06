# Technology Stack & Vendor Dependencies

## Backend

### Core Framework
| Komponen | Versi | Keterangan |
|----------|-------|------------|
| PHP | ^8.2 | Bahasa server-side |
| Laravel | ^12.0 | Framework utama |
| Laravel Breeze | ^2.3 | Auth scaffolding (dev) |

### Database & ORM
| Package | Versi | Keterangan |
|---------|-------|------------|
| Eloquent ORM | (built-in) | ORM Laravel |
| Doctrine DBAL | ^4.2 | Database abstraction untuk migration |

### Autentikasi & Autorisasi
| Package | Versi | Keterangan |
|---------|-------|------------|
| Laravel Sanctum | (built-in) | Token-based auth untuk API/mobile |
| Spatie Laravel Permission | ^6.20 | RBAC (role & permission) |

### Real-time
| Package | Versi | Keterangan |
|---------|-------|------------|
| Laravel Reverb | ^1.5 | WebSocket server (port 8080) |
| Laravel Echo | ^2.2.0 | WebSocket client (frontend) |
| Pusher.js | ^8.4.0 | Pusher protocol client |

### PDF & Dokumen
| Package | Versi | Keterangan |
|---------|-------|------------|
| Laravel DomPDF | ^3.1 | Generate PDF (sertifikat) |
| Browsershot | ^5.0 | HTML ke image/PDF via headless browser |
| PHPExcel/PHPSpreadsheet | ^1.30 | Handle spreadsheet |

### Import/Export
| Package | Versi | Keterangan |
|---------|-------|------------|
| Maatwebsite Excel | * | Import/export Excel/CSV |

### HTTP & Queue
| Package | Versi | Keterangan |
|---------|-------|------------|
| Guzzle HTTP Client | (built-in) | HTTP requests (Midtrans API) |
| Database Queue | (built-in) | Queue driver via database |

### Development Tools
| Package | Versi | Keterangan |
|---------|-------|------------|
| Laravel Pint | ^1.13 | Code style (PSR-12) |
| Laravel Sail | ^1.41 | Docker dev environment |
| Laravel Pail | ^1.2.2 | Log viewer |
| Laravel Tinker | ^2.10.1 | REPL |
| PHPUnit | ^11.5.3 | Testing |
| Faker | ^1.24 | Test data generation |
| Mockery | ^1.6 | Mocking |
| Collision | ^8.6 | Error reporting |

## Frontend

### CSS & UI
| Package | Versi | Keterangan |
|---------|-------|------------|
| Tailwind CSS | ^3.1.0 | Utility-first CSS framework |
| @tailwindcss/forms | ^0.5.2 | Form styling plugin |
| @tailwindcss/vite | ^4.0.0 | Tailwind Vite plugin |

### JavaScript
| Package | Versi | Keterangan |
|---------|-------|------------|
| Alpine.js | ^3.4.2 | Lightweight JS framework (interaktivitas) |
| @alpinejs/collapse | ^3.14.9 | Alpine collapse plugin |
| Axios | ^1.8.2 | HTTP client |
| interactjs | ^1.10.27 | Touch/drag interactions |

### Build Tools
| Package | Versi | Keterangan |
|---------|-------|------------|
| Vite | ^6.2.4 | Build tool & dev server |
| laravel-vite-plugin | ^1.2.0 | Vite integration untuk Laravel |
| PostCSS | ^8.4.31 | CSS processing |
| Autoprefixer | ^10.4.2 | CSS vendor prefix |
| Concurrently | ^9.0.1 | Run multiple dev processes |

### Rich Text Editor
| Tool | Keterangan |
|------|------------|
| Summernote | Loaded via CDN, bukan npm package |

## Third-Party Services

### Pembayaran
| Service | Keterangan |
|---------|------------|
| Midtrans Snap | Payment gateway (popup) |
| Midtrans Core API | Server-side transaction & verification |
| Midtrans Webhook | Payment notification callback |

### Hosting & Infrastructure
| Service | Keterangan |
|---------|------------|
| Reverb (WebSocket) | Real-time communication |
| Database (MySQL/SQLite) | Data storage |

## Konfigurasi Environment

```env
# Application
APP_NAME="BASS Training Center"
APP_LOCALE=id
APP_TIMEZONE=Asia/Jakarta

# Database
DB_CONNECTION=mysql
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

# Broadcasting (Real-time)
BROADCAST_CONNECTION=reverb
REVERB_PORT=8080

# Payment
MIDTRANS_MERCHANT_ID=
MIDTRANS_CLIENT_KEY=
MIDTRANS_SERVER_KEY=
MIDTRANS_IS_PRODUCTION=
```

## Perbandingan: Dev vs Production

| Aspek | Development | Production |
|-------|-------------|------------|
| Server | `php artisan serve` + Vite HMR | Nginx/Apache |
| Database | SQLite (default) | MySQL |
| Queue | `queue:listen` | Queue worker (supervisor) |
| WebSocket | Reverb (port 8080) | Reverb (behind proxy) |
| Assets | Vite dev server | `npm run build` |
| Error Display | Detailed | Hidden |
