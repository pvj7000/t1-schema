/**
 * TanStack Query hooks for Schema CRUD operations.
 */
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { globalsApi, localApi, healthApi, postsApi, postTypesApi, rulesApi, siteStructureApi, contextsApi, typesApi, variablesApi, settingsApi, scoreApi, recommendedRulesApi, customVariablesApi } from '../api/client';

// =============================================================================
// Schema Rules
// =============================================================================

export function useRules() {
  return useQuery({
    queryKey: ['rules'],
    queryFn: rulesApi.list,
  });
}

export function useCreateRule() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: rulesApi.create,
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['rules'] });
      qc.invalidateQueries({ queryKey: ['siteStructure'] });
    },
  });
}

export function useUpdateRule() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ id, ...data }) => rulesApi.update(id, data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['rules'] });
      qc.invalidateQueries({ queryKey: ['siteStructure'] });
    },
  });
}

export function useDeleteRule() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: rulesApi.delete,
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['rules'] });
      qc.invalidateQueries({ queryKey: ['siteStructure'] });
    },
  });
}

// =============================================================================
// Site Structure & Contexts
// =============================================================================

export function useSiteStructure() {
  return useQuery({
    queryKey: ['siteStructure'],
    queryFn: siteStructureApi.get,
  });
}

export function useContexts() {
  return useQuery({
    queryKey: ['contexts'],
    queryFn: contextsApi.list,
    staleTime: Infinity,
  });
}

// =============================================================================
// Post Types (CPT support)
// =============================================================================

export function usePostTypes() {
  return useQuery({
    queryKey: ['postTypes'],
    queryFn: postTypesApi.list,
    staleTime: Infinity,
  });
}

// =============================================================================
// Posts Browser
// =============================================================================

export function usePosts(params = {}) {
  return useQuery({
    queryKey: ['posts', params],
    queryFn: () => postsApi.list(params),
    keepPreviousData: true,
  });
}

// =============================================================================
// Local Schemas
// =============================================================================

export function useLocalSchemas(postId) {
  return useQuery({
    queryKey: ['local', postId],
    queryFn: () => localApi.get(postId),
    enabled: !!postId,
  });
}

export function useUpdateLocal() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ postId, schemas }) => localApi.update(postId, schemas),
    onSuccess: (_, variables) => {
      qc.invalidateQueries({ queryKey: ['local', variables.postId] });
      qc.invalidateQueries({ queryKey: ['posts'] });
    },
  });
}

export function usePostHealth(postId) {
  return useQuery({
    queryKey: ['health', 'post', postId],
    queryFn: () => healthApi.getPostHealth(postId),
    enabled: !!postId,
  });
}

// =============================================================================
// Global Schemas
// =============================================================================

export function useGlobals() {
  return useQuery({
    queryKey: ['globals'],
    queryFn: globalsApi.list,
  });
}

export function useGlobal(id) {
  return useQuery({
    queryKey: ['globals', id],
    queryFn: () => globalsApi.get(id),
    enabled: !!id,
  });
}

export function useCreateGlobal() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: globalsApi.create,
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['globals'] });
      qc.invalidateQueries({ queryKey: ['health'] });
    },
  });
}

export function useUpdateGlobal() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ id, ...data }) => globalsApi.update(id, data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['globals'] });
      qc.invalidateQueries({ queryKey: ['health'] });
    },
  });
}

export function useDeleteGlobal() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id) => globalsApi.delete(id),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['globals'] });
      qc.invalidateQueries({ queryKey: ['health'] });
    },
  });
}

// =============================================================================
// Health
// =============================================================================

export function useSiteHealth() {
  return useQuery({
    queryKey: ['health'],
    queryFn: healthApi.getSiteHealth,
  });
}

// =============================================================================
// Schema Types Registry
// =============================================================================

export function useSchemaTypes() {
  return useQuery({
    queryKey: ['types'],
    queryFn: typesApi.list,
    staleTime: Infinity, // Types don't change during a session.
  });
}

// =============================================================================
// Variables
// =============================================================================

export function useVariables() {
  return useQuery({
    queryKey: ['variables'],
    queryFn: variablesApi.list,
    staleTime: Infinity,
  });
}

// =============================================================================
// Settings
// =============================================================================

export function useSettings() {
  return useQuery({
    queryKey: ['settings'],
    queryFn: settingsApi.get,
  });
}

export function useUpdateSettings() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: settingsApi.update,
    onSuccess: () => qc.invalidateQueries({ queryKey: ['settings'] }),
  });
}

// =============================================================================
// Score
// =============================================================================

export function useScore() {
  return useQuery({
    queryKey: ['score'],
    queryFn: scoreApi.get,
  });
}

// =============================================================================
// Recommended Rules
// =============================================================================

export function useRecommendedRules() {
  return useQuery({
    queryKey: ['recommendedRules'],
    queryFn: recommendedRulesApi.list,
  });
}

export function useActivateRecommendedRule() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: recommendedRulesApi.activate,
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['recommendedRules'] });
      qc.invalidateQueries({ queryKey: ['rules'] });
      qc.invalidateQueries({ queryKey: ['score'] });
      qc.invalidateQueries({ queryKey: ['siteStructure'] });
    },
  });
}

// =============================================================================
// Custom Variables
// =============================================================================

export function useCustomVariables() {
  return useQuery({
    queryKey: ['customVariables'],
    queryFn: customVariablesApi.get,
  });
}

export function useUpdateCustomVariables() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: customVariablesApi.update,
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['customVariables'] });
      qc.invalidateQueries({ queryKey: ['variables'] });
    },
  });
}
