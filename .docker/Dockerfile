ARG NODE_VERSION=22

#
# Application base
#
FROM node:${NODE_VERSION}-alpine AS base
RUN set -eux \
    && apk update && apk upgrade --no-cache \
    && apk add --no-cache ca-certificates \
    && update-ca-certificates
ENV APP_ENV=production
ENV NODE_ENV=production
WORKDIR /app
EXPOSE 3000


#
# Development image (includes dev tools and dev dependencies)
#
FROM base AS development
ENV APP_ENV=development
ENV NODE_ENV=development
RUN set -eux \
    && adduser -S -u 1000 -G node local-user \
    && apk add --no-cache git
USER node


#
# Node.js production dependencies builder
#
FROM base AS deps-builder
COPY package.json package-lock.json* ./
RUN npm ci --omit=dev --ignore-scripts


#
# TypeScript compiler stage
#
FROM base AS ts-builder
COPY package.json package-lock.json* tsconfig.json ./
RUN npm ci --ignore-scripts
COPY src ./src
RUN npm run build


#
# Application (production)
#
FROM base AS production
# Compiled JavaScript
COPY --from=ts-builder /app/dist ./dist
# Production node_modules
COPY --from=deps-builder /app/node_modules ./node_modules
COPY --from=ts-builder /app/package.json ./package.json
# Static resources (guides, bmm, terminology, prompts) — unchanged from PHP version
COPY resources ./resources
USER node
CMD ["node", "dist/index.js"]
