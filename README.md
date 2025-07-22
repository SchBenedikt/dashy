# Dashy – Personal Dashboard for Nextcloud

A modern, customizable dashboard app for Nextcloud that allows users to create and personalize their workspace with interactive widgets. **All widgets integrate with real data from your Nextcloud apps.**

<img width="5088" height="2662"  src="https://github.com/user-attachments/assets/505bf542-e7b6-417e-9165-3b14b1a02895" />
<p float="left">
  <img alt="image" src="https://github.com/user-attachments/assets/dd37d4b9-fb5a-45ae-9fec-79758f6ffeb1" width="45%" />
  <img src="https://github.com/user-attachments/assets/5c71b774-5eb5-4310-99a9-07ab1f701efe" width="45%" />
</p>


[![License: AGPL v3](https://img.shields.io/badge/License-AGPL%20v3-blue.svg)](https://www.gnu.org/licenses/agpl-3.0)
[![Nextcloud App Store](https://img.shields.io/badge/Nextcloud-App%20Store-blue)](https://apps.nextcloud.com/)

## ✨ Overview

Dashy transforms your Nextcloud experience by providing a personalized dashboard where you can view and manage your most important information at a glance. Built with modern web technologies and following Nextcloud's design principles, Dashy offers a seamless integration with your existing workflow.

## 🚀 Features

### 🎯 Personalized Dashboard
- **Individual customization**: Each user creates their own unique dashboard
- **Persistent storage**: Dashboard configurations are saved per user
- **Intuitive interface**: Drag-and-drop functionality for easy management
- **Responsive design**: Works seamlessly across desktop and mobile devices

### 📐 Flexible Grid System
- **12-column layout**: Based on `vue-grid-layout` for maximum flexibility
- **Drag & drop**: Move widgets freely to create your perfect layout
- **Resizable widgets**: Adjust widget sizes to fit your content
- **Real-time updates**: Changes are saved automatically

### 🧩 Available Widgets (Real Data Integration)

#### 📅 Calendar Widget
- **Native integration** with Nextcloud Calendar app
- Displays upcoming events for the next 30 days
- Multi-calendar support with color coding
- Configurable event display count
- Shows location, time, and calendar information
- Click to view event details

#### ✅ Tasks Widget
- **Direct integration** with Nextcloud Tasks app
- Real-time task synchronization
- Create new tasks directly from the widget
- Mark tasks as complete with one click
- Delete tasks with confirmation
- Auto-refresh every 30 seconds
- Configurable maximum task display count

#### 🕐 Clock Widget
- Real-time clock with live updates
- 12-hour or 24-hour format options
- Optional seconds display
- Timezone information (optional)
- Elegant, minimal design

#### 🌤️ Weather Widget
- **Live weather data** from OpenWeatherMap API
- Current conditions for any location worldwide
- Temperature (actual and feels-like)
- Humidity, wind speed, and weather descriptions
- Configurable units (Celsius/Fahrenheit)
- Auto-refresh every 10 minutes
- Beautiful weather icons

#### 📝 Notes Widget
- **Integration** with Nextcloud Notes app
- Display recent notes from your Notes app
- Create and edit notes directly in the widget
- Folder-based organization support
- Quick preview and full edit capabilities
- Seamless sync with Notes app

### ⚙️ Widget Configuration
- Individual settings for each widget instance
- Easy access via gear icon in widget header
- Real-time preview of setting changes
- Persistent configuration storage
- Export/import capabilities (planned)

## 📦 Installation

### Requirements
- Nextcloud 25 or later
- PHP 8.0 or later
- Node.js 16 or later (for development)

### Quick Installation
1. **Download** the app to your Nextcloud apps directory:
   ```bash
   cd /path/to/nextcloud/apps
   git clone https://github.com/your-repo/dashy.git
   ```

2. **Install dependencies** and build:
   ```bash
   cd dashy
   npm install
   npm run build
   ```

3. **Enable the app** in Nextcloud:
   ```bash
   # Via OCC command
   sudo -u www-data php occ app:enable dashy
   
   # Or via the Nextcloud web interface:
   # Settings → Apps → Disabled Apps → Dashy → Enable
   ```

### Configuration

#### Weather Widget Setup
The Weather Widget requires an OpenWeatherMap API key for real weather data:

1. Get a free API key from [OpenWeatherMap](https://openweathermap.org/api)
2. Configure it in Nextcloud:
   ```bash
   sudo -u www-data php occ config:app:set dashy openweather_api_key --value="YOUR_API_KEY_HERE"
   ```

See [WEATHER_CONFIG.md](./WEATHER_CONFIG.md) for detailed weather configuration.

### Required Nextcloud Apps
For full functionality, these Nextcloud apps should be installed:

- **📅 Calendar**: Required for Calendar Widget
- **✅ Tasks**: Required for Tasks Widget  
- **📝 Notes**: Required for Notes Widget

*Note: Widgets will work without their corresponding apps but will show empty/no data.*

## 🏗️ Architecture & Technical Details

### Frontend Stack
- **Vue.js 2.7**: Modern reactive frontend framework
- **@nextcloud/vue**: Official Nextcloud UI component library
- **vue-grid-layout**: Drag & drop grid system for widget management
- **vue-material-design-icons**: Consistent iconography
- **@nextcloud/axios**: HTTP client for API communication
- **Vite**: Fast build tool and development server
- **SCSS**: Advanced CSS preprocessing

### Backend Stack
- **PHP 8+**: Server-side logic and API endpoints
- **Nextcloud App Framework**: Deep integration with Nextcloud ecosystem
- **OCS API**: RESTful API following Nextcloud standards
- **Calendar Manager**: Direct integration with Calendar app
- **Database Access**: Efficient data handling for Tasks and Notes

### Project Structure
```
dashy/
├── src/                    # Frontend Vue components
│   ├── components/
│   │   ├── widgets/       # Individual widget components
│   │   └── settings/      # Widget configuration components
│   ├── assets/            # Static assets
│   └── App.vue           # Main application component
├── lib/                   # Backend PHP logic
│   ├── Controller/        # API controllers
│   ├── Service/          # Business logic services
│   └── Migration/        # Database migrations
├── templates/             # PHP templates
├── css/                   # Compiled CSS files
├── appinfo/              # App metadata and routes
└── tests/                # Unit and integration tests
```

## 🔌 API Reference

The app provides a comprehensive REST API for dashboard and widget management:

### Dashboard Management
- `GET /apps/dashy/api/dashboard` - Load user's dashboard configuration
- `POST /apps/dashy/api/dashboard` - Save dashboard configuration

### Calendar Integration
- `GET /apps/dashy/api/calendar/events` - Fetch upcoming calendar events
- Parameters: `limit`, `calendar_ids`

### Tasks Integration
- `GET /apps/dashy/api/tasks` - Retrieve user tasks
- `POST /apps/dashy/api/tasks` - Create new task
- `PUT /apps/dashy/api/tasks/{id}` - Update existing task
- `DELETE /apps/dashy/api/tasks/{id}` - Delete task

### Notes Integration
- `GET /apps/dashy/api/notes` - Fetch notes from specified folder
- `POST /apps/dashy/api/notes` - Create new note
- `PUT /apps/dashy/api/notes/{id}` - Update note
- Parameters: `folder` (optional)

### Weather Integration
- `GET /apps/dashy/api/weather` - Get weather data for location
- Parameters: `location` (required), `unit` (metric/imperial)

*Full API documentation available in [openapi.json](./openapi.json)*

## 🛠️ Development

### Development Setup
1. **Clone the repository**:
   ```bash
   git clone https://github.com/your-repo/dashy.git
   cd dashy
   ```

2. **Install dependencies**:
   ```bash
   npm install
   ```

3. **Start development server**:
   ```bash
   npm run dev
   ```
   This starts Vite dev server with hot module replacement for rapid development.

### Available Scripts
```bash
# Development
npm run dev          # Start development server with HMR
npm run build        # Build for production
npm run preview      # Preview production build

# Code Quality
npm run lint         # ESLint for JavaScript/Vue
npm run stylelint    # Stylelint for CSS/SCSS
npm run lint:fix     # Auto-fix linting issues

# Testing
npm run test         # Run unit tests (if available)
```

### Building for Production
```bash
npm run build
```
This creates optimized bundles in the `js/` and `css/` directories.

### Code Style
- **ESLint**: JavaScript/Vue linting with Nextcloud configuration
- **Stylelint**: CSS/SCSS linting for consistent styles
- **Prettier**: Code formatting (recommended)

### Contributing Guidelines
1. Fork the repository
2. Create a feature branch: `git checkout -b feature/amazing-feature`
3. Make your changes following the code style
4. Run linting: `npm run lint`
5. Test your changes thoroughly
6. Commit using conventional commits: `git commit -m 'feat: add amazing feature'`
7. Push to your branch: `git push origin feature/amazing-feature`
8. Open a Pull Request

## 🔧 Extending Dashy

### Creating New Widgets
Dashy's widget system is designed to be easily extensible:

1. **Create widget component** in `src/components/widgets/`:
   ```vue
   <!-- YourWidget.vue -->
   <template>
     <div class="your-widget">
       <!-- Widget content -->
     </div>
   </template>
   ```

2. **Create settings component** in `src/components/widgets/`:
   ```vue
   <!-- YourWidgetSettings.vue -->
   <template>
     <div class="widget-settings">
       <!-- Settings form -->
     </div>
   </template>
   ```

3. **Register the widget** in `src/App.vue`:
   ```javascript
   import YourWidget from './components/widgets/YourWidget.vue'
   import YourWidgetSettings from './components/widgets/YourWidgetSettings.vue'
   
   // Add to components and availableWidgets
   ```

4. **Add backend endpoints** (if needed) in `lib/Controller/`

5. **Update API documentation** in `openapi.json`

### Widget Development Guidelines
- Follow Nextcloud design principles
- Implement proper error handling
- Add loading states for async operations
- Support responsive design
- Include proper accessibility attributes
- Document configuration options

## 🗺️ Roadmap

### Completed ✅
- [x] Calendar integration with Nextcloud Calendar app
- [x] Tasks integration with Nextcloud Tasks app  
- [x] Weather API integration with OpenWeatherMap
- [x] Notes integration with Nextcloud Notes app
- [x] Drag & drop grid layout system
- [x] Per-user dashboard persistence
- [x] Responsive design for mobile/desktop
- [x] Widget settings and configuration

### In Progress 🚧
- [ ] RSS Feed Widget for news aggregation
- [ ] Enhanced Notes Widget with folder management
- [ ] Widget themes and customization options
- [ ] Performance optimizations

### Planned 🎯
- [ ] **Mail Widget**: Integration with Nextcloud Mail app
- [ ] **News Widget**: Integration with Nextcloud News app
- [ ] **Files Widget**: Recent files and quick access
- [ ] **Bookmarks Widget**: Integration with Nextcloud Bookmarks
- [ ] **Contacts Widget**: Quick contact access and search
- [ ] **Shared Widgets**: Share widgets between users
- [ ] **Dashboard Templates**: Pre-configured dashboard layouts
- [ ] **Export/Import**: Dashboard configuration backup and restore
- [ ] **Widget Marketplace**: Community-contributed widgets
- [ ] **Advanced Theming**: Custom colors and styling options
- [ ] **Keyboard Shortcuts**: Power-user navigation
- [ ] **Mobile App**: Native mobile application

### Community Requests 💡
Have an idea for a new widget or feature? [Open an issue](https://github.com/your-repo/dashy/issues) to discuss it!

## 🤝 Contributing

We welcome contributions from the community! Here's how you can help:

### Ways to Contribute
- 🐛 **Bug Reports**: Found a bug? [Create an issue](https://github.com/your-repo/dashy/issues)
- 💡 **Feature Requests**: Have an idea? [Discuss it](https://github.com/your-repo/dashy/discussions)
- 🔧 **Code Contributions**: Submit pull requests with improvements
- 📖 **Documentation**: Help improve our docs and guides
- 🌐 **Translations**: Help translate Dashy to your language
- 🧪 **Testing**: Test new features and report feedback

### Development Community
- **Forum**: [Nextcloud Community Forum](https://help.nextcloud.com/c/dev/11)
- **Chat**: [Nextcloud Community Chat](https://cloud.nextcloud.com/call/xs25tz5y)
- **GitHub**: [Project Repository](https://github.com/your-repo/dashy)

## 📄 License

This project is licensed under the **AGPL-3.0-or-later** license. See the [LICENSE](LICENSE) file for details.

### Third-Party Licenses
- Vue.js: MIT License
- @nextcloud/vue: AGPL-3.0
- vue-grid-layout: MIT License
- OpenWeatherMap API: API Terms of Service

## 🙏 Acknowledgments

- **Nextcloud Team**: For the amazing platform and Vue components
- **Vue.js Community**: For the excellent framework and ecosystem
- **OpenWeatherMap**: For providing weather data
- **Contributors**: Everyone who has contributed to making Dashy better

---

**Made with ❤️ for the Nextcloud community**

*If you find Dashy useful, consider giving it a ⭐ on GitHub and sharing it with others!*
